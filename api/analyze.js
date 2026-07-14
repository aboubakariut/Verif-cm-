module.exports = async function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') return res.status(200).end();
  if (req.method !== 'POST') return res.status(405).json({ error: 'POST only' });

  try {
    const { image, prompt } = req.body || {};
    const apiKey = process.env.GEMINI_API_KEY;

    if (!apiKey) return res.status(500).json({ error: 'Clé API manquante' });
    if (!image)  return res.status(400).json({ error: 'Image manquante' });
    if (!prompt) return res.status(400).json({ error: 'Prompt manquant' });

    const https = require('https');

    const payload = JSON.stringify({
      contents: [{
        parts: [
          { inline_data: { mime_type: 'image/jpeg', data: image } },
          { text: prompt }
        ]
      }],
      generationConfig: { temperature: 0.1, maxOutputTokens: 4096 }
    });

    const result = await new Promise((resolve, reject) => {
      const options = {
        hostname: 'generativelanguage.googleapis.com',
        path: `/v1beta/models/gemini-2.5-flash:generateContent?key=${apiKey}`,
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Content-Length': Buffer.byteLength(payload)
        }
      };
      const r = https.request(options, (response) => {
        let data = '';
        response.on('data', chunk => data += chunk);
        response.on('end', () => resolve({ status: response.statusCode, data }));
      });
      r.on('error', (e) => reject(new Error('Erreur réseau: ' + e.message)));
      r.write(payload);
      r.end();
    });

    let parsed;
    try {
      parsed = JSON.parse(result.data);
    } catch (e) {
      return res.status(500).json({ error: 'Réponse Gemini non JSON', raw: result.data.substring(0, 200) });
    }

    if (result.status !== 200) {
      return res.status(result.status).json({ error: parsed });
    }

    let text = parsed.candidates?.[0]?.content?.parts?.[0]?.text || '';
    if (!text) return res.status(500).json({ error: 'Réponse vide' });

    // Nettoyer les balises markdown
    text = text.replace(/```json/g, '').replace(/```/g, '').trim();

    // ── RÉPARATION JSON CÔTÉ SERVEUR ──────────────────────
    let resultJson;

    // Tentative 1 : parse direct
    try {
      resultJson = JSON.parse(text);
    } catch (e1) {

      // Tentative 2 : couper au dernier champ complet
      // On cherche la dernière virgule suivie d'un champ complet
      const repair1 = repairJson(text);
      try {
        resultJson = JSON.parse(repair1);
      } catch (e2) {

        // Tentative 3 : extraire juste les champs essentiels par regex
        resultJson = extractEssentials(text);
        if (!resultJson) {
          return res.status(500).json({ error: 'JSON invalide', raw: text.substring(0, 300) });
        }
      }
    }

    // Tronquer les valeurs trop longues pour éviter l'affichage bizarre
    if (resultJson.cs && resultJson.cs.length > 80) {
      resultJson.cs = resultJson.cs.substring(0, 77) + '...';
    }

    return res.status(200).json({ text: JSON.stringify(resultJson) });

  } catch (error) {
    return res.status(500).json({ error: error.message || 'Erreur inconnue' });
  }
};

// Répare un JSON tronqué en coupant au dernier champ complet
function repairJson(text) {
  // Trouver la dernière virgule avant une clé (ex: ,"cs":"...)
  // et couper là, puis fermer avec }
  let lastGood = -1;
  // Chercher la position du dernier champ COMPLET (valeur fermée)
  // Un champ complet finit par : "valeur", ou nombre, ou true/false
  const pattern = /,\s*"[^"]+"\s*:\s*(?:"[^"]*"|[0-9]+|true|false|\[[^\]]*\])\s*(?=,)/g;
  let match;
  while ((match = pattern.exec(text)) !== null) {
    lastGood = match.index + match[0].length;
  }
  if (lastGood > 0) {
    return text.substring(0, lastGood) + '}';
  }
  // Fallback : fermer brutalement
  return text.replace(/,\s*"[^"]*$/, '}').replace(/[^}]$/, '}');
}

// Extrait les champs essentiels par regex si tout le reste échoue
function extractEssentials(text) {
  try {
    const get = (key) => {
      const m = text.match(new RegExp(`"${key}"\\s*:\\s*"([^"]*?)"`));
      return m ? m[1] : 'N/A';
    };
    const getNum = (key) => {
      const m = text.match(new RegExp(`"${key}"\\s*:\\s*(\\d+)`));
      return m ? parseInt(m[1]) : 50;
    };
    const getBool = (key) => {
      const m = text.match(new RegExp(`"${key}"\\s*:\\s*(true|false)`));
      return m ? m[1] === 'true' : false;
    };
    return {
      verdict: get('verdict') || 'SUSPECT',
      nom: get('nom'), fab: get('fab'), lot: get('lot'),
      exp: get('exp'), pays: get('pays'),
      type: get('type'), inst: get('inst'), tit: get('tit'),
      ref: get('ref'), dt: get('dt'),
      sec: [], ano: [],
      sc: getNum('sc'),
      al: getBool('al'),
      cs: get('cs').substring(0, 80)
    };
  } catch (e) {
    return null;
  }
}
