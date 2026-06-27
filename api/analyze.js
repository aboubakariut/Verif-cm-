module.exports = async function handler(req, res) {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') return res.status(200).end();
  if (req.method !== 'POST') return res.status(405).json({ error: 'POST only' });

  try {
    // Vercel parse automatiquement le body JSON — req.body est déjà disponible
    const image = req.body?.image;
    const prompt = req.body?.prompt;

    const apiKey = process.env.GEMINI_API_KEY;
    if (!apiKey) return res.status(500).json({ error: 'Clé API manquante' });
    if (!image) return res.status(400).json({ error: 'Image manquante' });
    if (!prompt) return res.status(400).json({ error: 'Prompt manquant' });

    const https = require('https');

    const payload = JSON.stringify({
      contents: [{
        parts: [
          { inline_data: { mime_type: 'image/jpeg', data: image } },
          { text: prompt }
        ]
      }],
      generationConfig: { temperature: 0.1, maxOutputTokens: 2048 }
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
      return res.status(500).json({ error: 'Réponse Gemini non JSON', raw: result.data.substring(0, 300) });
    }

    if (result.status !== 200) {
      return res.status(result.status).json({ error: parsed });
    }

    let text = parsed.candidates?.[0]?.content?.parts?.[0]?.text || '';
    if (!text) return res.status(500).json({ error: 'Réponse vide de Gemini', raw: JSON.stringify(parsed).substring(0, 300) });

    // Nettoyer la réponse de Gemini
    text = text.replace(/```json/g, '').replace(/```/g, '').trim();

    // Vérifier que c'est du JSON valide
    let resultJson;
    try {
      resultJson = JSON.parse(text);
    } catch (e) {
      // Tenter de corriger les apostrophes mal placées
      try {
        const fixed = text
          .replace(/'/g, '"')
          .replace(/(\w+)":/g, '"$1":')
          .replace(/:\s*"([^"]*)"([^,}\]])/g, ': "$1"$2');
        resultJson = JSON.parse(fixed);
      } catch (e2) {
        return res.status(500).json({ error: 'JSON invalide', raw: text.substring(0, 300) });
      }
    }

    return res.status(200).json({ text: JSON.stringify(resultJson) });

  } catch (e) {
    return res.status(500).json({ error: e.message || 'Erreur inconnue', type: e.constructor.name });
  }
};
