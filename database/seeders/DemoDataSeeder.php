<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\HostingAccount;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\Tld;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Data contoh supaya dashboard & halaman CRUD langsung terisi.
     * Aman dijalankan berulang kali (pakai firstOrCreate berbasis email).
     */
    public function run(): void
    {
        $clientsData = [
            ['name' => 'Budi Santoso', 'email' => 'budi.santoso@example.com', 'company' => null, 'phone' => '0812-1111-2222'],
            ['name' => 'Siti Aminah', 'email' => 'siti.aminah@example.com', 'company' => null, 'phone' => '0813-2222-3333'],
            ['name' => 'PT Maju Jaya', 'email' => 'admin@majujaya.co.id', 'company' => 'PT Maju Jaya', 'phone' => '021-555-1234'],
            ['name' => 'Andi Wijaya', 'email' => 'andi.wijaya@example.com', 'company' => null, 'phone' => '0815-4444-5555'],
            ['name' => 'Dewi Lestari', 'email' => 'dewi.lestari@example.com', 'company' => null, 'phone' => '0816-6666-7777'],
        ];

        $clients = collect($clientsData)->map(function ($data) {
            return Client::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'company' => $data['company'],
                    'phone' => $data['phone'],
                    'city' => 'Jakarta',
                    'country' => 'Indonesia',
                    'status' => 'active',
                ]
            );
        });

        $orderSeed = [
            ['client' => 0, 'product' => 'Cloud Hosting - Pro', 'type' => 'hosting', 'amount' => 350000, 'status' => 'pending'],
            ['client' => 1, 'product' => 'Domain .com', 'type' => 'domain', 'amount' => 150000, 'status' => 'active'],
            ['client' => 2, 'product' => 'VPS - Business', 'type' => 'vps', 'amount' => 1200000, 'status' => 'active'],
            ['client' => 3, 'product' => 'Shared Hosting - Starter', 'type' => 'hosting', 'amount' => 75000, 'status' => 'suspended'],
            ['client' => 4, 'product' => 'Domain .id', 'type' => 'domain', 'amount' => 225000, 'status' => 'pending'],
        ];

        foreach ($orderSeed as $row) {
            $client = $clients[$row['client']];

            $hostingAccount = null;
            if ($row['type'] === 'hosting') {
                $hostingAccount = HostingAccount::firstOrCreate(
                    ['client_id' => $client->id, 'domain' => strtolower(str_replace(' ', '', $client->name)) . '.com'],
                    [
                        'package' => $row['product'],
                        'panel' => 'cpanel',
                        'price' => $row['amount'],
                        'billing_cycle' => 'monthly',
                        'status' => $row['status'] === 'active' ? 'active' : ($row['status'] === 'suspended' ? 'suspended' : 'pending'),
                        'next_due_date' => now()->addDays(30),
                    ]
                );
            }

            $order = Order::firstOrCreate(
                ['client_id' => $client->id, 'product_name' => $row['product']],
                [
                    'hosting_account_id' => $hostingAccount?->id,
                    'order_type' => $row['type'],
                    'amount' => $row['amount'],
                    'status' => $row['status'],
                ]
            );

            if ($row['status'] === 'active') {
                Invoice::firstOrCreate(
                    ['order_id' => $order->id],
                    [
                        'client_id' => $client->id,
                        'amount' => $row['amount'],
                        'tax' => 0,
                        'total' => $row['amount'],
                        'status' => 'paid',
                        'issue_date' => now()->subDays(5),
                        'due_date' => now()->addDays(2),
                        'paid_at' => now()->subDays(2),
                        'payment_method' => 'Transfer Bank',
                    ]
                );
            }
        }

        // TLD contoh (harga jual) — belum terhubung ke registrar sungguhan,
        // hubungkan lewat menu Registrar setelah kredensial Namecheap siap.
        $tldSeed = [
            ['extension' => '.com', 'register' => 150000, 'renew' => 165000, 'transfer' => 150000],
            ['extension' => '.net', 'register' => 175000, 'renew' => 190000, 'transfer' => 175000],
            ['extension' => '.id', 'register' => 225000, 'renew' => 225000, 'transfer' => 225000],
            ['extension' => '.co.id', 'register' => 125000, 'renew' => 125000, 'transfer' => 125000],
        ];

        foreach ($tldSeed as $row) {
            Tld::firstOrCreate(
                ['extension' => $row['extension']],
                [
                    'register_price' => $row['register'],
                    'renew_price' => $row['renew'],
                    'transfer_price' => $row['transfer'],
                    'min_years' => 1,
                    'max_years' => 10,
                    'is_active' => true,
                ]
            );
        }

        // Gateway transfer manual sebagai contoh — langsung bisa dipakai
        // tanpa kredensial API apapun.
        PaymentGateway::firstOrCreate(
            ['driver' => 'manual', 'name' => 'Transfer Bank (Manual)'],
            [
                'mode' => 'production',
                'instructions' => "Bank BCA\nNo. Rekening: 1234567890\na/n PT Contoh Hosting\n\nSetelah transfer, kirim bukti ke support@contohhosting.com",
                'fee_flat' => 0,
                'fee_percent' => 0,
                'currency' => 'IDR',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );
    }
}
