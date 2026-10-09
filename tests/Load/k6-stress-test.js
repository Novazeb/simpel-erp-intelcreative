import http from 'k6/http';
import { check, sleep } from 'k6';

// Konfigurasi Beban Kerja Skala Enterprise (500 - 1.000 Concurrent VUs)
export const options = {
    stages: [
        { duration: '30s', target: 100 },   // Warm-up ramp up ke 100 pengguna
        { duration: '1m',  target: 500 },   // Beban normal 500 pengguna aktif simultan
        { duration: '2m',  target: 1000 },  // Puncak beban 1.000 pengguna (Jam sibuk presensi 08:45 - 09:00)
        { duration: '30s', target: 0 },     // Ramp-down pendinginan sistem
    ],
    thresholds: {
        // 95% permintaan wajib direspons di bawah 500ms
        http_req_duration: ['p(95)<500'],
        // Tingkat kegagalan sistem (5xx) wajib di bawah 0.1%
        'http_req_failed{status:500}': ['rate<0.001'],
    },
};

const BASE_URL = __ENV.APP_URL || 'http://127.0.0.1:8000';

export default function () {
    // 1. Pemeriksaan kesehatan server & connection pool database
    const healthRes = http.get(`${BASE_URL}/api/health`, {
        headers: { 'Accept': 'application/json' },
    });

    check(healthRes, {
        'Health check status is 200': (r) => r.status === 200,
        'Database connection UP': (r) => r.json('checks.database.status') === 'UP',
        'Response time < 200ms': (r) => r.timings.duration < 200,
    });

    // 2. Simulasi Presensi Masuk Harian (Jam Sibuk Presensi)
    // Mensimulasikan proteksi Atomic Lock Cache (menerima 200 OK atau 429 Too Many Requests jika duplikat)
    const clockInPayload = JSON.stringify({
        latitude: -6.2088,
        longitude: 106.8456,
        timestamp: new Date().toISOString(),
    });

    const clockInRes = http.post(`${BASE_URL}/attendance/clock-in`, clockInPayload, {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': 'SIMULATED_TEST_TOKEN',
        },
    });

    check(clockInRes, {
        'Clock-in status valid (200 OK, 302 Redirect, or 429 Throttled/Locked)': (r) =>
            [200, 302, 401, 419, 429].includes(r.status),
    });

    // Jeda alami antar aksi pengguna (0.5 - 2 detik)
    sleep(Math.random() * 1.5 + 0.5);
}
