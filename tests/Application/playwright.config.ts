import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: '../e2e',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: 1,
    reporter: process.env.CI ? 'github' : 'list',
    use: {
        baseURL: process.env.BASE_URL || 'https://127.0.0.1:8000',
        ignoreHTTPSErrors: true,
        screenshot: 'only-on-failure',
        trace: 'on-first-retry',
    },
    projects: [
        {
            name: 'setup',
            testMatch: /.*\.setup\.ts/,
        },
        {
            name: 'chromium',
            use: {
                browserName: 'chromium',
                storageState: '../e2e/.auth/admin.json',
            },
            dependencies: ['setup'],
        },
    ],
    webServer: (process.env.CI || process.env.BASE_URL) ? undefined : {
        command: 'php -S 127.0.0.1:8000 -t public',
        url: 'http://127.0.0.1:8000',
        reuseExistingServer: true,
        timeout: 30000,
    },
});
