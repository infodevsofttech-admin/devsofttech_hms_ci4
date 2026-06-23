<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AbdmLocationMasterSeeder extends Seeder
{
    /**
     * Canonical India state/UT code map used by ABDM payloads.
     *
     * @return array<string, string>
     */
    private function stateMap(): array
    {
        return [
            '1' => 'JAMMU AND KASHMIR',
            '2' => 'HIMACHAL PRADESH',
            '3' => 'PUNJAB',
            '4' => 'CHANDIGARH',
            '5' => 'UTTARAKHAND',
            '6' => 'HARYANA',
            '7' => 'DELHI',
            '8' => 'RAJASTHAN',
            '9' => 'UTTAR PRADESH',
            '10' => 'BIHAR',
            '11' => 'SIKKIM',
            '12' => 'ARUNACHAL PRADESH',
            '13' => 'NAGALAND',
            '14' => 'MANIPUR',
            '15' => 'MIZORAM',
            '16' => 'TRIPURA',
            '17' => 'MEGHALAYA',
            '18' => 'ASSAM',
            '19' => 'WEST BENGAL',
            '20' => 'JHARKHAND',
            '21' => 'ODISHA',
            '22' => 'CHHATTISGARH',
            '23' => 'MADHYA PRADESH',
            '24' => 'GUJARAT',
            '25' => 'DAMAN AND DIU',
            '26' => 'DADRA AND NAGAR HAVELI',
            '27' => 'MAHARASHTRA',
            '28' => 'ANDHRA PRADESH',
            '29' => 'KARNATAKA',
            '30' => 'GOA',
            '31' => 'LAKSHADWEEP',
            '32' => 'KERALA',
            '33' => 'TAMIL NADU',
            '34' => 'PUDUCHERRY',
            '35' => 'ANDAMAN AND NICOBAR ISLANDS',
            '36' => 'TELANGANA',
            '37' => 'ANDHRA PRADESH',
            '38' => 'LADAKH',
        ];
    }

    /**
     * District seed data.
     * state_code '5' = Uttarakhand (ABDM numeric code, matches india_state.id).
     * District codes follow NIC format (UK01–UK13).
     * The legacy ABDM-numeric entry (code 56) is kept for backward compatibility.
     *
     * @return array<int, array{district_code:string,district_name:string,state_code:string}>
     */
    private function districtSeeds(): array
    {
        return [
            // Legacy ABDM-numeric code (from live ABHA verify responses)
            ['district_code' => '56',   'district_name' => 'UDHAM SINGH NAGAR', 'state_code' => '5'],

            // Uttarakhand — NIC district codes
            ['district_code' => 'UK01', 'district_name' => 'ALMORA',            'state_code' => '5'],

            // Uttar Pradesh — NIC district codes (state_code '9' = india_state.id)
            ['district_code' => 'UP01', 'district_name' => 'AGRA',                         'state_code' => '9'],
            ['district_code' => 'UP02', 'district_name' => 'ALIGARH',                      'state_code' => '9'],
            ['district_code' => 'UP03', 'district_name' => 'AMBEDKAR NAGAR',               'state_code' => '9'],
            ['district_code' => 'UP04', 'district_name' => 'AMETHI',                       'state_code' => '9'],
            ['district_code' => 'UP05', 'district_name' => 'AMROHA',                       'state_code' => '9'],
            ['district_code' => 'UP06', 'district_name' => 'AURAIYA',                      'state_code' => '9'],
            ['district_code' => 'UP07', 'district_name' => 'AYODHYA',                      'state_code' => '9'],
            ['district_code' => 'UP08', 'district_name' => 'AZAMGARH',                     'state_code' => '9'],
            ['district_code' => 'UP09', 'district_name' => 'BAGHPAT',                      'state_code' => '9'],
            ['district_code' => 'UP10', 'district_name' => 'BAHRAICH',                     'state_code' => '9'],
            ['district_code' => 'UP11', 'district_name' => 'BALLIA',                       'state_code' => '9'],
            ['district_code' => 'UP12', 'district_name' => 'BALRAMPUR',                    'state_code' => '9'],
            ['district_code' => 'UP13', 'district_name' => 'BANDA',                        'state_code' => '9'],
            ['district_code' => 'UP14', 'district_name' => 'BARABANKI',                    'state_code' => '9'],
            ['district_code' => 'UP15', 'district_name' => 'BAREILLY',                     'state_code' => '9'],
            ['district_code' => 'UP16', 'district_name' => 'BASTI',                        'state_code' => '9'],
            ['district_code' => 'UP17', 'district_name' => 'BHADOHI',                      'state_code' => '9'],
            ['district_code' => 'UP18', 'district_name' => 'BIJNOR',                       'state_code' => '9'],
            ['district_code' => 'UP19', 'district_name' => 'BUDAUN',                       'state_code' => '9'],
            ['district_code' => 'UP20', 'district_name' => 'BULANDSHAHR',                  'state_code' => '9'],
            ['district_code' => 'UP21', 'district_name' => 'CHANDAULI',                    'state_code' => '9'],
            ['district_code' => 'UP22', 'district_name' => 'CHITRAKOOT',                   'state_code' => '9'],
            ['district_code' => 'UP23', 'district_name' => 'DEORIA',                       'state_code' => '9'],
            ['district_code' => 'UP24', 'district_name' => 'ETAH',                         'state_code' => '9'],
            ['district_code' => 'UP25', 'district_name' => 'ETAWAH',                       'state_code' => '9'],
            ['district_code' => 'UP26', 'district_name' => 'FARRUKHABAD',                  'state_code' => '9'],
            ['district_code' => 'UP27', 'district_name' => 'FATEHPUR',                     'state_code' => '9'],
            ['district_code' => 'UP28', 'district_name' => 'FIROZABAD',                    'state_code' => '9'],
            ['district_code' => 'UP29', 'district_name' => 'GAUTAM BUDDHA NAGAR',          'state_code' => '9'],
            ['district_code' => 'UP30', 'district_name' => 'GHAZIABAD',                    'state_code' => '9'],
            ['district_code' => 'UP31', 'district_name' => 'GHAZIPUR',                     'state_code' => '9'],
            ['district_code' => 'UP32', 'district_name' => 'GONDA',                        'state_code' => '9'],
            ['district_code' => 'UP33', 'district_name' => 'GORAKHPUR',                    'state_code' => '9'],
            ['district_code' => 'UP34', 'district_name' => 'HAMIRPUR',                     'state_code' => '9'],
            ['district_code' => 'UP35', 'district_name' => 'HAPUR',                        'state_code' => '9'],
            ['district_code' => 'UP36', 'district_name' => 'HARDOI',                       'state_code' => '9'],
            ['district_code' => 'UP37', 'district_name' => 'HATHRAS',                      'state_code' => '9'],
            ['district_code' => 'UP38', 'district_name' => 'JALAUN',                       'state_code' => '9'],
            ['district_code' => 'UP39', 'district_name' => 'JAUNPUR',                      'state_code' => '9'],
            ['district_code' => 'UP40', 'district_name' => 'JHANSI',                       'state_code' => '9'],
            ['district_code' => 'UP41', 'district_name' => 'KANNAUJ',                      'state_code' => '9'],
            ['district_code' => 'UP42', 'district_name' => 'KANPUR DEHAT',                 'state_code' => '9'],
            ['district_code' => 'UP43', 'district_name' => 'KANPUR NAGAR',                 'state_code' => '9'],
            ['district_code' => 'UP44', 'district_name' => 'KANSHIRAM NAGAR (KASGANJ)',    'state_code' => '9'],
            ['district_code' => 'UP45', 'district_name' => 'KAUSHAMBI',                    'state_code' => '9'],
            ['district_code' => 'UP46', 'district_name' => 'KUSHINAGAR',                   'state_code' => '9'],
            ['district_code' => 'UP47', 'district_name' => 'LAKHIMPUR KHERI',              'state_code' => '9'],
            ['district_code' => 'UP48', 'district_name' => 'LALITPUR',                     'state_code' => '9'],
            ['district_code' => 'UP49', 'district_name' => 'LUCKNOW',                      'state_code' => '9'],
            ['district_code' => 'UP50', 'district_name' => 'MAHARAJGANJ',                  'state_code' => '9'],
            ['district_code' => 'UP51', 'district_name' => 'MAHOBA',                       'state_code' => '9'],
            ['district_code' => 'UP52', 'district_name' => 'MAINPURI',                     'state_code' => '9'],
            ['district_code' => 'UP53', 'district_name' => 'MATHURA',                      'state_code' => '9'],
            ['district_code' => 'UP54', 'district_name' => 'MAU',                          'state_code' => '9'],
            ['district_code' => 'UP55', 'district_name' => 'MEERUT',                       'state_code' => '9'],
            ['district_code' => 'UP56', 'district_name' => 'MIRZAPUR',                     'state_code' => '9'],
            ['district_code' => 'UP57', 'district_name' => 'MORADABAD',                    'state_code' => '9'],
            ['district_code' => 'UP58', 'district_name' => 'MUZAFFARNAGAR',                'state_code' => '9'],
            ['district_code' => 'UP59', 'district_name' => 'PILIBHIT',                     'state_code' => '9'],
            ['district_code' => 'UP60', 'district_name' => 'PRATAPGARH',                   'state_code' => '9'],
            ['district_code' => 'UP61', 'district_name' => 'PRAYAGRAJ',                    'state_code' => '9'],
            ['district_code' => 'UP62', 'district_name' => 'RAE BARELI',                   'state_code' => '9'],
            ['district_code' => 'UP63', 'district_name' => 'RAMPUR',                       'state_code' => '9'],
            ['district_code' => 'UP64', 'district_name' => 'SAHARANPUR',                   'state_code' => '9'],
            ['district_code' => 'UP65', 'district_name' => 'SAMBHAL',                      'state_code' => '9'],
            ['district_code' => 'UP66', 'district_name' => 'SANT KABIR NAGAR',             'state_code' => '9'],
            ['district_code' => 'UP67', 'district_name' => 'SHAHJAHANPUR',                 'state_code' => '9'],
            ['district_code' => 'UP68', 'district_name' => 'SHAMLI',                       'state_code' => '9'],
            ['district_code' => 'UP69', 'district_name' => 'SHRAVASTI',                    'state_code' => '9'],
            ['district_code' => 'UP70', 'district_name' => 'SIDDHARTHNAGAR',               'state_code' => '9'],
            ['district_code' => 'UP71', 'district_name' => 'SITAPUR',                      'state_code' => '9'],
            ['district_code' => 'UP72', 'district_name' => 'SONBHADRA',                    'state_code' => '9'],
            ['district_code' => 'UP73', 'district_name' => 'SULTANPUR',                    'state_code' => '9'],
            ['district_code' => 'UP74', 'district_name' => 'UNNAO',                        'state_code' => '9'],
            ['district_code' => 'UP75', 'district_name' => 'VARANASI',                     'state_code' => '9'],
            ['district_code' => 'UK02', 'district_name' => 'BAGESHWAR',         'state_code' => '5'],
            ['district_code' => 'UK03', 'district_name' => 'CHAMOLI',           'state_code' => '5'],
            ['district_code' => 'UK04', 'district_name' => 'CHAMPAWAT',         'state_code' => '5'],
            ['district_code' => 'UK05', 'district_name' => 'DEHRADUN',          'state_code' => '5'],
            ['district_code' => 'UK06', 'district_name' => 'HARIDWAR',          'state_code' => '5'],
            ['district_code' => 'UK07', 'district_name' => 'NAINITAL',          'state_code' => '5'],
            ['district_code' => 'UK08', 'district_name' => 'PAURI GARHWAL',     'state_code' => '5'],
            ['district_code' => 'UK09', 'district_name' => 'PITHORAGARH',       'state_code' => '5'],
            ['district_code' => 'UK10', 'district_name' => 'RUDRAPRAYAG',       'state_code' => '5'],
            ['district_code' => 'UK11', 'district_name' => 'TEHRI GARHWAL',     'state_code' => '5'],
            ['district_code' => 'UK12', 'district_name' => 'UDHAM SINGH NAGAR', 'state_code' => '5'],
            ['district_code' => 'UK13', 'district_name' => 'UTTARKASHI',        'state_code' => '5'],
        ];
    }

    public function run(): void
    {
        if (! $this->db->tableExists('abdm_district_master')) {
            // Table is created by migration 2026-06-24-000610.
            return;
        }

        $now = date('Y-m-d H:i:s');

        // State reference data lives in india_state (pre-seeded). No writes needed here.

        // Upsert districts by unique (district_code, state_code).
        $districtTable = $this->db->table('abdm_district_master');
        foreach ($this->districtSeeds() as $row) {
            $existing = $districtTable
                ->select('id')
                ->where('district_code', $row['district_code'])
                ->where('state_code', $row['state_code'])
                ->limit(1)
                ->get()
                ->getRowArray();

            if ($existing) {
                $districtTable->where('id', (int) ($existing['id'] ?? 0))->update([
                    'district_name' => $row['district_name'],
                    'updated_at' => $now,
                ]);
                continue;
            }

            $districtTable->insert([
                'district_code' => $row['district_code'],
                'district_name' => $row['district_name'],
                'state_code' => $row['state_code'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
