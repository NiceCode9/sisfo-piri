module.exports = {
    apps: [
        {
            name: 'wa-gateway',
            script: 'src/index.js',
            instances: 1,
            autorestart: true,
            watch: false,
            env: {
                NODE_ENV: 'production',
                PORT: 3001,
                HOST: '127.0.0.1',
            },
        },
    ],
};
