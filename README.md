# VÉRIF-CM — Plateforme Nationale de Vérification Intelligente

**IPT — Informatique Pour Tous · Ngaoundéré, Cameroun**  
Concours National du Meilleur Projet TIC 2026 · MINPOSTEL

---

## Structure du projet

```
verif-cm/
├── index.html       → Interface utilisateur (MédiScan + DocScan)
├── api/
│   └── analyze.js  → Endpoint serveur sécurisé (clé API cachée)
├── vercel.json      → Configuration Vercel
└── README.md
```

---

## Obtenir la clé Gemini API (100% gratuit)

1. Va sur **https://aistudio.google.com**
2. Connecte-toi avec ton compte Google
3. Clique **Get API Key** → **Create API key**
4. Copie la clé : `AIzaSy...`

Limites gratuites : **15 requêtes/minute · 1500/jour** — largement suffisant.

---

## Déploiement sur Vercel

### 1. Créer un compte Vercel
→ https://vercel.com (gratuit, connexion avec GitHub ou Google)

### 2. Déployer
1. Aller sur https://vercel.com/new
2. Glisser le dossier `verif-cm/` ou connecter GitHub
3. Cliquer **Deploy**

### 3. Ajouter la clé API
1. Aller dans **Settings** → **Environment Variables**
2. Ajouter :
   - **Name** : `GEMINI_API_KEY`
   - **Value** : `AIzaSyxxxxxxxxxxxxxxxx`
3. Cliquer **Save** → **Redeploy**

---

## Technologies utilisées

| Composant | Technologie |
|-----------|-------------|
| Interface | HTML5 · CSS3 · JavaScript |
| Serveur | Node.js (Vercel Serverless) |
| IA | Gemini 1.5 Flash (Google) — Gratuit |
| Hébergement | Vercel (gratuit) |

---

## Modules

### 💊 MédiScan
Vérification de médicaments par analyse d'image IA.
- Upload photo de la boîte / plaquette / flacon
- Extraction : nom, fabricant, lot, date, pays
- Verdict : AUTHENTIQUE / SUSPECT / CONTREFAIT
- Alerte automatique si contrefaçon détectée

### 📄 DocScan
Vérification de documents officiels.
- Types : Diplôme, Acte de naissance, Permis, CNI, Attestation, Certificat médical
- Analyse : sceaux, signatures, hologrammes, numérotation
- Verdict : AUTHENTIQUE / SUSPECT / FALSIFIÉ

---

## Contact

**ABOUBAKAR Siddiki**  
Fondateur · IPT — Informatique Pour Tous  
Ngaoundéré, Adamaoua, Cameroun  
+237 695 703 571
