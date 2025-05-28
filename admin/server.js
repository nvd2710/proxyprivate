// server.js
const express = require('express');
const cors = require('cors');
const fs = require('fs').promises;
const path = require('path');
const crypto = require('crypto');

const app = express();
const PORT = 3000;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.static('public'));

// Database simulation (JSON files)
const DB_PATH = './data';
const USERS_FILE = path.join(DB_PATH, 'users.json');
const PROXIES_FILE = path.join(DB_PATH, 'proxies.json');
const ORDERS_FILE = path.join(DB_PATH, 'orders.json');

// Initialize database
async function initDB() {
    try {
        await fs.mkdir(DB_PATH, { recursive: true });
        
        // Initialize users file
        try {
            await fs.access(USERS_FILE);
        } catch {
            await fs.writeFile(USERS_FILE, JSON.stringify([]));
        }
        
        // Initialize proxies file
        try {
            await fs.access(PROXIES_FILE);
        } catch {
            await fs.writeFile(PROXIES_FILE, JSON.stringify([]));
        }
        
        // Initialize orders file
        try {
            await fs.access(ORDERS_FILE);
        } catch {
            await fs.writeFile(ORDERS_FILE, JSON.stringify([]));
        }
    } catch (error) {
        console.error('Database initialization error:', error);
    }
}

// Helper functions
async function readJSON(filePath) {
    try {
        const data = await fs.readFile(filePath, 'utf8');
        return JSON.parse(data);
    } catch {
        return [];
    }
}

async function writeJSON(filePath, data) {
    await fs.writeFile(filePath, JSON.stringify(data, null, 2));
}

function generateId() {
    return crypto.randomBytes(16).toString('hex');
}

function generateApiKey() {
    return crypto.randomBytes(16).toString('hex').match(/.{8}/g).join('-');
}

// Authentication middleware
function authenticateToken(req, res, next) {
    const authHeader = req.headers['authorization'];
    const token = authHeader && authHeader.split(' ')[1];
    
    if (!token) {
        return res.status(401).json({ error: 'Access token required' });
    }
    
    // Simple token validation (in production, use JWT)
    req.userId = token;
    next();
}

// Routes

// Register
app.post('/api/register', async (req, res) => {
    try {
        const { username, password } = req.body;
        
        // Validation
        if (!username || !password) {
            return res.status(400).json({ 
                error: 'Tên tài khoản và mật khẩu là bắt buộc' 
            });
        }
        
        if (username.length < 3) {
            return res.status(400).json({ 
                error: 'Tên tài khoản phải có ít nhất 3 ký tự' 
            });
        }
        
        if (password.length < 6) {
            return res.status(400).json({ 
                error: 'Mật khẩu phải có ít nhất 6 ký tự' 
            });
        }
        
        const users = await readJSON(USERS_FILE);
        
        // Check if username exists
        if (users.find(user => user.username === username)) {
            return res.status(400).json({ 
                error: 'Tên tài khoản đã tồn tại' 
            });
        }
        
        // Create new user
        const newUser = {
            id: generateId(),
            username,
            password, // In production, hash this
            balance: 0,
            apiKey: generateApiKey(),
            createdAt: new Date().toISOString()
        };
        
        users.push(newUser);
        await writeJSON(USERS_FILE, users);
        
        res.json({ 
            message: 'Đăng ký thành công',
            user: {
                id: newUser.id,
                username: newUser.username,
                balance: newUser.balance,
                apiKey: newUser.apiKey
            }
        });
        
    } catch (error) {
        console.error('Register error:', error);
        res.status(500).json({ error: 'Lỗi server' });
    }
});

// Login
app.post('/api/login', async (req, res) => {
    try {
        const { username, password } = req.body;
        
        if (!username || !password) {
            return res.status(400).json({ 
                error: 'Tên tài khoản và mật khẩu là bắt buộc' 
            });
        }
        
        const users = await readJSON(USERS_FILE);
        const user = users.find(u => u.username === username && u.password === password);
        
        if (!user) {
            return res.status(401).json({ 
                error: 'Tên tài khoản hoặc mật khẩu không đúng' 
            });
        }
        
        res.json({
            message: 'Đăng nhập thành công',
            token: user.id, // Simple token (use JWT in production)
            user: {
                id: user.id,
                username: user.username,
                balance: user.balance,
                apiKey: user.apiKey
            }
        });
        
    } catch (error) {
        console.error('Login error:', error);
        res.status(500).json({ error: 'Lỗi server' });
    }
});

// Get user info
app.get('/api/user', authenticateToken, async (req, res) => {
    try {
        const users = await readJSON(USERS_FILE);
        const user = users.find(u => u.id === req.userId);
        
        if (!user) {
            return res.status(404).json({ error: 'Không tìm thấy người dùng' });
        }
        
        res.json({
            id: user.id,
            username: user.username,
            balance: user.balance,
            apiKey: user.apiKey
        });
        
    } catch (error) {
        console.error('Get user error:', error);
        res.status(500).json({ error: 'Lỗi server' });
    }
});

// Get proxy pricing
app.get('/api/pricing', (req, res) => {
    const pricing = {
        ipv6: { 3: 5000, 7: 10000, 30: 15000, 90: 40000 },
        ipv4: { 3: 15000, 7: 30000, 30: 45000, 90: 120000 },
        'ipv4-shared': { 3: 8000, 7: 15000, 30: 20000, 90: 55000 }
    };
    res.json(pricing);
});

// Get available proxy counts
app.get('/api/available', (req, res) => {
    const available = {
        vn: { ipv4: 1250, ipv6: 5000, shared: 2500 },
        us: { ipv4: 3500, ipv6: 15000, shared: 7500 },
        sg: { ipv4: 800, ipv6: 3200, shared: 1600 },
        jp: { ipv4: 1200, ipv6: 4800, shared: 2400 },
        kr: { ipv4: 900, ipv6: 3600, shared: 1800 },
        th: { ipv4: 600, ipv6: 2400, shared: 1200 }
    };
    res.json(available);
});

// Buy proxy
app.post('/api/buy', authenticateToken, async (req, res) => {
    try {
        const { type, count, period, country } = req.body;
        
        // Validation
        if (!type || !count || !period || !country) {
            return res.status(400).json({ 
                error: 'Thiếu thông tin đặt hàng' 
            });
        }
        
        const pricing = {
            ipv6: { 3: 5000, 7: 10000, 30: 15000, 90: 40000 },
            ipv4: { 3: 15000, 7: 30000, 30: 45000, 90: 120000 },
            'ipv4-shared': { 3: 8000, 7: 15000, 30: 20000, 90: 55000 }
        };
        
        const basePrice = pricing[type]?.[period];
        if (!basePrice) {
            return res.status(400).json({ error: 'Gói proxy không hợp lệ' });
        }
        
        const totalPrice = basePrice * count;
        
        // Check user balance
        const users = await readJSON(USERS_FILE);
        const userIndex = users.findIndex(u => u.id === req.userId);
        
        if (userIndex === -1) {
            return res.status(404).json({ error: 'Không tìm thấy người dùng' });
        }
        
        if (users[userIndex].balance < totalPrice) {
            return res.status(400).json({ 
                error: 'Số dư không đủ. Vui lòng nạp thêm tiền.' 
            });
        }
        
        // Create order
        const order = {
            id: generateId(),
            userId: req.userId,
            type,
            count,
            period,
            country,
            totalPrice,
            status: 'completed',
            createdAt: new Date().toISOString(),
            expiresAt: new Date(Date.now() + period * 24 * 60 * 60 * 1000).toISOString()
        };
        
        // Generate proxies
        const proxies = [];
        for (let i = 0; i < count; i++) {
            proxies.push({
                id: generateId(),
                orderId: order.id,
                userId: req.userId,
                ip: generateMockIP(type, country),
                port: 8080 + Math.floor(Math.random() * 1000),
                username: `user_${generateId().substring(0, 8)}`,
                password: generateId().substring(0, 12),
                type: type.includes('ipv6') ? 'IPv6' : 'IPv4',
                protocol: 'HTTP/HTTPS',
                country,
                status: 'active',
                createdAt: order.createdAt,
                expiresAt: order.expiresAt,
                comment: ''
            });
        }
        
        // Update user balance
        users[userIndex].balance -= totalPrice;
        
        // Save data
        await writeJSON(USERS_FILE, users);
        
        const orders = await readJSON(ORDERS_FILE);
        orders.push(order);
        await writeJSON(ORDERS_FILE, orders);
        
        const allProxies = await readJSON(PROXIES_FILE);
        allProxies.push(...proxies);
        await writeJSON(PROXIES_FILE, allProxies);
        
        res.json({
            message: 'Mua proxy thành công',
            order,
            proxies,
            newBalance: users[userIndex].balance
        });
        
    } catch (error) {
        console.error('Buy proxy error:', error);
        res.status(500).json({ error: 'Lỗi server' });
    }
});

// Get user proxies
app.get('/api/proxies', authenticateToken, async (req, res) => {
    try {
        const proxies = await readJSON(PROXIES_FILE);
        const userProxies = proxies.filter(p => p.userId === req.userId);
        res.json(userProxies);
    } catch (error) {
        console.error('Get proxies error:', error);
        res.status(500).json({ error: 'Lỗi server' });
    }
});

// Tools endpoints
app.post('/api/tools/check-proxy', (req, res) => {
    const { proxy } = req.body;
    
    if (!proxy) {
        return res.status(400).json({ error: 'Proxy không hợp lệ' });
    }
    
    // Simulate proxy check
    setTimeout(() => {
        const isWorking = Math.random() > 0.3;
        res.json({
            proxy,
            status: isWorking ? 'working' : 'failed',
            responseTime: Math.floor(Math.random() * 1000) + 100,
            country: 'Vietnam',
            anonymity: isWorking ? 'High' : 'Unknown'
        });
    }, 1000);
});

app.get('/api/tools/my-ip', (req, res) => {
    const clientIP = req.headers['x-forwarded-for'] || req.connection.remoteAddress;
    res.json({
        ip: clientIP === '::1' ? '127.0.0.1' : clientIP,
        country: 'Vietnam',
        city: 'Ho Chi Minh City',
        isp: 'Viettel'
    });
});

app.post('/api/tools/whois', (req, res) => {
    const { domain } = req.body;
    
    if (!domain) {
        return res.status(400).json({ error: 'Domain không hợp lệ' });
    }
    
    // Mock whois data
    res.json({
        domain,
        registrar: 'GoDaddy',
        createdDate: '2020-01-01',
        expiryDate: '2025-01-01',
        nameServers: ['ns1.example.com', 'ns2.example.com'],
        status: 'Active'
    });
});

// Helper function to generate mock IPs
function generateMockIP(type, country) {
    if (type.includes('ipv6')) {
        return `2001:db8:${Math.floor(Math.random() * 65536).toString(16)}::${Math.floor(Math.random() * 65536).toString(16)}`;
    } else {
        const countryRanges = {
            vn: '103.123',
            us: '192.168',
            sg: '175.176',
            jp: '210.211',
            kr: '220.221',
            th: '202.203'
        };
        const prefix = countryRanges[country] || '192.168';
        return `${prefix}.${Math.floor(Math.random() * 256)}.${Math.floor(Math.random() * 256)}`;
    }
}

// Start server
initDB().then(() => {
    app.listen(PORT, () => {
        console.log(`Server running on http://localhost:${PORT}`);
    });
});
