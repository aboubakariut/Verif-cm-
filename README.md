# VÉRIF-CM — Plateforme Nationale de Vérification Intelligente

**IPT — Informatique Pour Tous · Ngaoundéré, Cameroun**
Concours National du Meilleur Projet TIC 2026 · MINPOSTEL

---

## Présentation

VÉRIF-CM est une plateforme web progressive (PWA) alimentée par l'intelligence artificielle Gemini 2.5 Flash de Google. Elle permet à tout Camerounais de vérifier instantanément l'authenticité d'un médicament ou d'un document officiel depuis son smartphone ou ordinateur.

### Deux modules

| Module | Description |
|--------|-------------|
| 💊 **MédiScan** | Vérification de médicaments par analyse d'image IA — boîte, plaquette, flacon |
| 📄 **DocScan** | Vérification de documents officiels — diplômes, actes, permis, CNI, attestations, certificats médicaux |

---

## Structure du projet

```
verif-cm/
├── index.html        → Interface complète (MédiScan + DocScan + styles responsive)
├── api/
│   └── analyze.js   → Fonction serverless sécurisée (proxy vers Gemini API)
├── package.json      → Configuration Node.js (version 24.x)
├── vercel.json       → Configuration déploiement Vercel
└── README.md
```

---

## Technologies utilisées

| Couche | Technologie |
|--------|-------------|
| Interface | HTML5 · CSS3 · JavaScript (PWA) |
| Serveur | Node.js — Vercel Serverless Functions |
| Intelligence Artificielle | Google Gemini 2.5 Flash API |
| Hébergement | Vercel (gratuit) |
| Base de données | Aucune — analyse par IA en temps réel |

---

## Obtenir la clé API Gemini (100% gratuit)

1. Aller sur **https://aistudio.google.com/apikey**
2. Se connecter avec un compte Google
3. Cliquer **Get API Key** → **Create API key**
4. Copier la clé générée

Limites gratuites : **15 requêtes/minute · 1 500/jour** — suffisant pour la démonstration.

---

## Déploiement sur Vercel via GitHub

### Étape 1 — Préparer le dépôt GitHub
1. Créer un dépôt GitHub nommé `verif-cm`
2. Uploader tous les fichiers en respectant la structure ci-dessus
3. S'assurer que `api/analyze.js` est bien dans un dossier `api/` à la racine

### Étape 2 — Connecter à Vercel
1. Aller sur **https://vercel.com/new**
2. Cliquer **Import Git Repository**
3. Sélectionner le dépôt `verif-cm`
4. Cliquer **Deploy**

### Étape 3 — Ajouter la clé API
1. Aller dans **Settings** → **Environment Variables**
2. Ajouter :
   - **Name** : `GEMINI_API_KEY`
   - **Value** : votre clé Gemini
3. Cliquer **Save** → **Redeploy**

### Mise à jour du code
Modifier un fichier sur GitHub → Vercel redéploie automatiquement en moins de 60 secondes.

---

## Fonctionnement

```
Utilisateur (smartphone)
        ↓
Photo ou import d'image
        ↓
Compression locale (max 800px, qualité 70%)
        ↓
Envoi vers /api/analyze (serveur Vercel)
        ↓
Appel Gemini 2.5 Flash API
        ↓
Analyse IA de l'image
        ↓
Verdict : AUTHENTIQUE / SUSPECT / CONTREFAIT ou FALSIFIÉ
        ↓
Alerte automatique si risque détecté
```

---

## Verdicts possibles

### MédiScan
| Verdict | Couleur | Signification |
|---------|---------|---------------|
| ✅ AUTHENTIQUE | Vert | Médicament identifié sans anomalie |
| ⚠️ SUSPECT | Orange | Anomalies détectées — consulter un pharmacien |
| ❌ CONTREFAIT | Rouge | Signes de contrefaçon — ne pas consommer |

### DocScan
| Verdict | Couleur | Signification |
|---------|---------|---------------|
| ✅ AUTHENTIQUE | Vert | Document présentant tous les éléments de sécurité |
| ⚠️ SUSPECT | Orange | Éléments inhabituels — vérification recommandée |
| ❌ FALSIFIÉ | Rouge | Signes de falsification — ne pas accepter |

---

## Types de documents supportés (DocScan)

- 🎓 Diplôme
- 📋 Acte de naissance
- 🚗 Permis de conduire
- 🪪 Carte Nationale d'Identité (CNI)
- 💼 Attestation de travail
- 🏥 Certificat médical

---

## Codes d'erreur fréquents

| Code | Cause | Solution |
|------|-------|----------|
| 503 | Gemini surchargé | Attendre 2-5 minutes et réessayer |
| 403 | Clé API invalide | Vérifier dans Vercel → Environment Variables |
| 500 | Erreur interne | Réessayer — souvent temporaire |
| 404 | Fonction non trouvée | Vérifier que `api/analyze.js` est à la racine du dépôt |

---

## Sécurité

- La clé API n'est **jamais** exposée dans le code frontend
- Elle est stockée exclusivement dans les variables d'environnement Vercel
- Les images sont compressées localement et ne sont pas stockées sur les serveurs
- Ne jamais commiter la clé API sur GitHub

---

## Renouveler la clé API

Si le quota gratuit est atteint (1 500 requêtes/jour) :
1. Créer une nouvelle clé sur https://aistudio.google.com/apikey
2. Mettre à jour dans Vercel : **Settings** → **Environment Variables** → modifier `GEMINI_API_KEY`
3. **Redeploy**

---

## Contact

**ABOUBAKAR Siddiki**
Fondateur · IPT — Informatique Pour Tous
Ngaoundéré, Adamaoua, Cameroun
+237 695 703 571
