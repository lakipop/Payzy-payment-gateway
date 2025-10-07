const express = require('express');
const axios = require('axios');
const cors = require('cors'); 
const app = express();
const PORT = process.env.PORT || 3000;


// Enable CORS for all routes
app.use(cors());

// Middleware to parse JSON request bodies
app.use(express.json());


// Middleware to parse JSON request bodies
app.use(express.json());

// Root route
app.get('/', (req, res) => {
    res.send('Hello, World!');
});


app.post('/api/externalData', async (req, res) => {
    const receivedData = req.body;
   
    const externalUrl = 'https://api.payzypay.xyz/checkout/custom-checkout';
    const data = await axios.post(externalUrl, receivedData)
    console.log(data.data);
    return res.status(201).json({
        message: "Data received and processed successfully",
        data: data.data
    });
});


// Start the server
app.listen(PORT, () => {
    console.log(`Server is running on http://localhost:${PORT}`);
});