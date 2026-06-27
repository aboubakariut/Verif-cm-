module.exports = function handler(req, res) {
  const apiKey = process.env.GEMINI_API_KEY;
  res.status(200).json({
    status: 'ok',
    method: req.method,
    hasApiKey: !!apiKey,
    keyLength: apiKey ? apiKey.length : 0,
    body: req.body || null,
    nodeVersion: process.version
  });
};
