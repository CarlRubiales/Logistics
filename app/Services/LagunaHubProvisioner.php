<?php

namespace App\Services;

use App\Models\DestinationArea;
use App\Models\Hub;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class LagunaHubProvisioner
{
    /**
     * All 30 municipalities and cities in the Province of Laguna.
     *
     * @return array<int, array{branch: string, slug: string, email: string, password: string, postal_code: string, aliases?: array<int, string>, extra_emails?: array<int, string>, extra_passwords?: array<int, string>}>
     */
    public static function municipalities(): array
    {
        return [
            ['branch' => 'Alaminos', 'slug' => 'alaminos', 'email' => 'alaminos@gmail.com', 'password' => 'alaminos1234', 'postal_code' => '4001'],
            ['branch' => 'Bay', 'slug' => 'bay', 'email' => 'bay@gmail.com', 'password' => 'bay1234', 'postal_code' => '4033'],
            ['branch' => 'Binan', 'slug' => 'binan', 'email' => 'binan@gmail.com', 'password' => 'binan1234', 'postal_code' => '4024', 'aliases' => ['biñan'], 'extra_emails' => ['biñan@gmail.com'], 'extra_passwords' => ['biñan1234']],
            ['branch' => 'Cabuyao', 'slug' => 'cabuyao', 'email' => 'cabuyao@gmail.com', 'password' => 'cabuyao1234', 'postal_code' => '4025'],
            ['branch' => 'Calamba', 'slug' => 'calamba', 'email' => 'calamba@gmail.com', 'password' => 'calamba1234', 'postal_code' => '4027'],
            ['branch' => 'Calauan', 'slug' => 'calauan', 'email' => 'calauan@gmail.com', 'password' => 'calauan1234', 'postal_code' => '4012'],
            ['branch' => 'Cavinti', 'slug' => 'cavinti', 'email' => 'cavinti@gmail.com', 'password' => 'cavinti1234', 'postal_code' => '4013'],
            ['branch' => 'Famy', 'slug' => 'famy', 'email' => 'famy@gmail.com', 'password' => 'famy1234', 'postal_code' => '4021'],
            ['branch' => 'Kalayaan', 'slug' => 'kalayaan', 'email' => 'kalayaan@gmail.com', 'password' => 'kalayaan1234', 'postal_code' => '4015'],
            ['branch' => 'Liliw', 'slug' => 'liliw', 'email' => 'liliw@gmail.com', 'password' => 'liliw1234', 'postal_code' => '4004'],
            ['branch' => 'Los Banos', 'slug' => 'losbanos', 'email' => 'losbanos@gmail.com', 'password' => 'losbanos1234', 'postal_code' => '4030', 'aliases' => ['los banos', 'los baños', 'losbaños'], 'extra_emails' => ['los banos@gmail.com', 'losbaños@gmail.com', 'los baños@gmail.com'], 'extra_passwords' => ['los banos1234', 'losbaños1234', 'los baños1234']],
            ['branch' => 'Luisiana', 'slug' => 'luisiana', 'email' => 'luisiana@gmail.com', 'password' => 'luisiana1234', 'postal_code' => '4032'],
            ['branch' => 'Lumban', 'slug' => 'lumban', 'email' => 'lumban@gmail.com', 'password' => 'lumban1234', 'postal_code' => '4014'],
            ['branch' => 'Mabitac', 'slug' => 'mabitac', 'email' => 'mabitac@gmail.com', 'password' => 'mabitac1234', 'postal_code' => '4020'],
            ['branch' => 'Magdalena', 'slug' => 'magdalena', 'email' => 'magdalena@gmail.com', 'password' => 'magdalena1234', 'postal_code' => '4007'],
            ['branch' => 'Majayjay', 'slug' => 'majayjay', 'email' => 'majayjay@gmail.com', 'password' => 'majayjay1234', 'postal_code' => '4005'],
            ['branch' => 'Nagcarlan', 'slug' => 'nagcarlan', 'email' => 'nagcarlan@gmail.com', 'password' => 'nagcarlan1234', 'postal_code' => '4002'],
            ['branch' => 'Paete', 'slug' => 'paete', 'email' => 'paete@gmail.com', 'password' => 'paete1234', 'postal_code' => '4016'],
            ['branch' => 'Pagsanjan', 'slug' => 'pagsanjan', 'email' => 'pagsanjan@gmail.com', 'password' => 'pagsanjan1234', 'postal_code' => '4008'],
            ['branch' => 'Pakil', 'slug' => 'pakil', 'email' => 'pakil@gmail.com', 'password' => 'pakil1234', 'postal_code' => '4017'],
            ['branch' => 'Pangil', 'slug' => 'pangil', 'email' => 'pangil@gmail.com', 'password' => 'pangil1234', 'postal_code' => '4018'],
            ['branch' => 'Pila', 'slug' => 'pila', 'email' => 'pila@gmail.com', 'password' => 'pila1234', 'postal_code' => '4010', 'extra_passwords' => ['pila123']],
            ['branch' => 'Rizal', 'slug' => 'rizal', 'email' => 'rizal@gmail.com', 'password' => 'rizal1234', 'postal_code' => '4003'],
            ['branch' => 'San Pablo', 'slug' => 'sanpablo', 'email' => 'sanpablo@gmail.com', 'password' => 'sanpablo1234', 'postal_code' => '4000', 'aliases' => ['san pablo'], 'extra_emails' => ['san pablo@gmail.com'], 'extra_passwords' => ['san pablo1234']],
            ['branch' => 'San Pedro', 'slug' => 'sanpedro', 'email' => 'sanpedro@gmail.com', 'password' => 'sanpedro1234', 'postal_code' => '4023', 'aliases' => ['san pedro'], 'extra_emails' => ['san pedro@gmail.com'], 'extra_passwords' => ['san pedro1234']],
            ['branch' => 'Santa Cruz', 'slug' => 'santacruz', 'email' => 'santacruz@gmail.com', 'password' => 'santacruz1234', 'postal_code' => '4009', 'aliases' => ['santa cruz', 'sta cruz', 'stacruz'], 'extra_emails' => ['stacruz@gmail.com', 'santa cruz@gmail.com', 'sta cruz@gmail.com'], 'extra_passwords' => ['stacruz1234', 'santa cruz1234', 'sta cruz1234']],
            ['branch' => 'Santa Maria', 'slug' => 'santamaria', 'email' => 'santamaria@gmail.com', 'password' => 'santamaria1234', 'postal_code' => '4022', 'aliases' => ['santa maria', 'sta maria', 'stamaria'], 'extra_emails' => ['stamaria@gmail.com', 'santa maria@gmail.com', 'sta maria@gmail.com'], 'extra_passwords' => ['stamaria1234', 'santa maria1234', 'sta maria1234']],
            ['branch' => 'Santa Rosa', 'slug' => 'santarosa', 'email' => 'santarosa@gmail.com', 'password' => 'santarosa1234', 'postal_code' => '4026', 'aliases' => ['santa rosa', 'sta rosa', 'starosa'], 'extra_emails' => ['starosa@gmail.com', 'santa rosa@gmail.com', 'sta rosa@gmail.com'], 'extra_passwords' => ['starosa1234', 'santa rosa1234', 'sta rosa1234']],
            ['branch' => 'Siniloan', 'slug' => 'siniloan', 'email' => 'siniloan@gmail.com', 'password' => 'siniloan1234', 'postal_code' => '4019'],
            ['branch' => 'Victoria', 'slug' => 'victoria', 'email' => 'victoria@gmail.com', 'password' => 'victoria1234', 'postal_code' => '4011'],
        ];
    }

    /**
     * Ensure all 30 Laguna hubs, destination areas, and branch admin accounts exist in the database.
     */
    public static function ensureProvisioned(bool $force = false): void
    {
        $cacheKey = 'laguna_30_hubs_provisioned_v1';
        if (! $force && Cache::has($cacheKey)) {
            return;
        }

        try {
            if (! Schema::hasTable('users')) {
                return;
            }

            $hasHubsTable = Schema::hasTable('hubs');
            $hasAreasTable = Schema::hasTable('destination_areas');
            $storeId = null;

            if ($hasAreasTable && Schema::hasTable('stores')) {
                $store = Store::firstOrCreate(
                    ['external_store_id' => 'lumora-demo'],
                    [
                        'name' => 'Lumora Logistics',
                        'platform' => 'custom',
                        'api_base_url' => 'https://lumora.party/api',
                        'is_active' => true,
                    ]
                );
                $storeId = $store->id;
            }

            foreach (self::municipalities() as $item) {
                $branch = $item['branch'];
                $slug = $item['slug'];
                $email = $item['email'];
                $password = $item['password'];
                $postalCode = $item['postal_code'];
                $upperName = strtoupper($branch);
                $code = 'SCN-' . strtoupper($slug);

                // 1. Provision Branch Admin User
                $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
                if (! $user) {
                    User::create([
                        'name' => $upperName,
                        'email' => $email,
                        'password' => Hash::make($password),
                        'role' => 'admin',
                        'branch' => $branch,
                    ]);
                } else {
                    $updates = [];
                    if ($user->branch !== $branch) {
                        $updates['branch'] = $branch;
                    }
                    if ($user->role !== 'admin') {
                        $updates['role'] = 'admin';
                    }
                    if (! empty($updates)) {
                        $user->update($updates);
                    }
                }

                // Also provision explicit shorthand alias user if applicable (e.g. stacruz@gmail.com)
                foreach ($item['extra_emails'] ?? [] as $extraEmail) {
                    if (! str_contains($extraEmail, ' ') && ! str_contains($extraEmail, 'ñ')) {
                        $aliasUser = User::whereRaw('LOWER(email) = ?', [strtolower($extraEmail)])->first();
                        $aliasPassword = str_replace('@gmail.com', '', strtolower($extraEmail)) . '1234';
                        if (! $aliasUser) {
                            User::create([
                                'name' => $upperName,
                                'email' => strtolower($extraEmail),
                                'password' => Hash::make($aliasPassword),
                                'role' => 'admin',
                                'branch' => $branch,
                            ]);
                        } elseif ($aliasUser->branch !== $branch) {
                            $aliasUser->update(['branch' => $branch, 'role' => 'admin']);
                        }
                    }
                }

                // 2. Provision Hub Record
                if ($hasHubsTable) {
                    $hub = Hub::where('branch', $branch)
                        ->orWhere('city', $branch)
                        ->orWhere('name', "{$branch} Hub")
                        ->first();

                    if (! $hub) {
                        Hub::create([
                            'name' => "{$branch} Hub",
                            'branch' => $branch,
                            'city' => $branch,
                            'address' => "{$branch} Sorting & Distribution Center, Laguna",
                            'is_active' => true,
                        ]);
                    } else {
                        $hub->update([
                            'branch' => $branch,
                            'city' => $hub->city ?: $branch,
                            'is_active' => true,
                        ]);
                    }
                }

                // 3. Provision DestinationArea Record
                if ($hasAreasTable && $storeId) {
                    $area = DestinationArea::where('branch', $branch)
                        ->orWhere('code', $code)
                        ->first();

                    if (! $area) {
                        DestinationArea::create([
                            'store_id' => $storeId,
                            'name' => $code,
                            'branch' => $branch,
                            'province' => 'Laguna',
                            'code' => $code,
                            'cities' => [$branch],
                            'postal_codes' => [$postalCode],
                            'is_active' => true,
                        ]);
                    } else {
                        $area->update([
                            'branch' => $branch,
                            'province' => 'Laguna',
                            'is_active' => true,
                        ]);
                    }
                }
            }

            Cache::put($cacheKey, true, now()->addHours(12));
        } catch (\Throwable $e) {
            // Non-blocking if DB is migrating
        }
    }

    /**
     * Match an incoming login email & password against any Laguna municipality hub,
     * supporting spaces, ñ, and Sta./Santa variations, and return the authenticated User if valid.
     */
    public static function resolveAndVerifyUser(string $rawEmail, string $rawPassword): ?User
    {
        self::ensureProvisioned();

        $email = mb_strtolower(trim($rawEmail));
        $password = trim($rawPassword);

        // Normalize email local part (before @)
        $localPart = str_contains($email, '@') ? explode('@', $email)[0] : $email;
        $normalizedLocal = str_replace([' ', '.', '-', '_', 'ñ'], ['', '', '', '', 'n'], $localPart);

        foreach (self::municipalities() as $item) {
            $slug = $item['slug'];
            $allEmails = array_merge([$item['email']], $item['extra_emails'] ?? []);
            $allAliases = array_merge([$slug, mb_strtolower($item['branch'])], $item['aliases'] ?? []);
            $normalizedAliases = array_map(
                fn ($a) => str_replace([' ', '.', '-', '_', 'ñ'], ['', '', '', '', 'n'], mb_strtolower($a)),
                $allAliases
            );

            $emailMatched = in_array($email, array_map('mb_strtolower', $allEmails), true)
                || in_array($normalizedLocal, $normalizedAliases, true);

            if (! $emailMatched) {
                continue;
            }

            $user = User::whereRaw('LOWER(email) = ?', [$email])->first()
                ?? User::whereRaw('LOWER(email) = ?', [$item['email']])->first();

            if (! $user) {
                $user = User::create([
                    'name' => strtoupper($item['branch']),
                    'email' => $item['email'],
                    'password' => Hash::make($item['password']),
                    'role' => 'admin',
                    'branch' => $item['branch'],
                ]);
            }

            $validPasswords = array_merge(
                [$item['password'], $slug . '1234', $slug . '123', mb_strtolower($item['branch']) . '1234'],
                $item['extra_passwords'] ?? []
            );
            $normalizedPassword = str_replace([' ', '.', '-', '_', 'ñ'], ['', '', '', '', 'n'], mb_strtolower($password));
            $normalizedValidPasswords = array_map(
                fn ($p) => str_replace([' ', '.', '-', '_', 'ñ'], ['', '', '', '', 'n'], mb_strtolower($p)),
                $validPasswords
            );

            if (
                Hash::check($password, $user->password)
                || in_array(mb_strtolower($password), array_map('mb_strtolower', $validPasswords), true)
                || in_array($normalizedPassword, $normalizedValidPasswords, true)
            ) {
                if ($user->branch !== $item['branch'] || $user->role !== 'admin') {
                    $user->update([
                        'branch' => $item['branch'],
                        'role' => 'admin',
                    ]);
                }
                return $user;
            }
        }

        return null;
    }
}
