<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateComplaintsMasterTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'Code' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'Name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'show_in_short' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'name_hinglish' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'keywords' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'ai_hint' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('Code', true);
        $this->forge->addKey('show_in_short');
        $this->forge->addKey('is_active');
        $this->forge->createTable('complaints_master', true);

        $rows = [
            ['Code' => 1, 'Name' => 'FEVER', 'show_in_short' => 1, 'name_hinglish' => 'bukhar', 'keywords' => 'fever,bukhar,taap,high temperature', 'ai_hint' => 'fever with duration and pattern'],
            ['Code' => 2, 'Name' => 'PAIN JOINTS', 'show_in_short' => 1, 'name_hinglish' => 'jodo ka dard', 'keywords' => 'joint pain,jodo ka dard,arthralgia', 'ai_hint' => 'joint pain with site and severity'],
            ['Code' => 4, 'Name' => 'PAIN BACK', 'show_in_short' => 1, 'name_hinglish' => 'kamar dard', 'keywords' => 'back pain,kamar dard,low back pain', 'ai_hint' => 'back pain with radiation and activity relation'],
            ['Code' => 5, 'Name' => 'PAIN CHEST', 'show_in_short' => 1, 'name_hinglish' => 'chhati mein dard', 'keywords' => 'chest pain,chhati dard,retrosternal pain', 'ai_hint' => 'chest pain with exertional relation'],
            ['Code' => 6, 'Name' => 'PAIN', 'show_in_short' => 1, 'name_hinglish' => 'dard', 'keywords' => 'pain,dard,ache', 'ai_hint' => 'pain with site duration and score'],
            ['Code' => 7, 'Name' => 'BREATHING DIFFICULTY', 'show_in_short' => 1, 'name_hinglish' => 'saans lene mein dikkat', 'keywords' => 'breathing difficulty,dyspnea,saans ki dikkat', 'ai_hint' => 'dyspnea with onset and positional variation'],
            ['Code' => 10, 'Name' => 'GHABRAHAT', 'show_in_short' => 1, 'name_hinglish' => 'ghabrahat', 'keywords' => 'ghabrahat,anxiety,palpitation', 'ai_hint' => 'anxiety-like symptoms with trigger'],
            ['Code' => 13, 'Name' => 'COUGH DRY', 'show_in_short' => 1, 'name_hinglish' => 'sukhi khansi', 'keywords' => 'dry cough,sukhi khansi,cough', 'ai_hint' => 'cough with type and duration'],
            ['Code' => 14, 'Name' => 'COUGH WITH EXPECTORATION', 'show_in_short' => 1, 'name_hinglish' => 'balgam wali khansi', 'keywords' => 'productive cough,balgam,cough with expectoration', 'ai_hint' => 'cough with sputum color/amount'],
            ['Code' => 18, 'Name' => 'VOMITING', 'show_in_short' => 1, 'name_hinglish' => 'ulti', 'keywords' => 'vomiting,ulti,emesis', 'ai_hint' => 'vomiting frequency and associated nausea'],
            ['Code' => 20, 'Name' => 'DIARRHOEA', 'show_in_short' => 1, 'name_hinglish' => 'dast', 'keywords' => 'diarrhoea,dast,loose stool', 'ai_hint' => 'loose stools with frequency/dehydration signs'],
            ['Code' => 21, 'Name' => 'CONSTIPATION', 'show_in_short' => 1, 'name_hinglish' => 'kabz', 'keywords' => 'constipation,kabz,hard stool', 'ai_hint' => 'constipation with stool frequency'],
            ['Code' => 28, 'Name' => 'JAUNDICE', 'show_in_short' => 1, 'name_hinglish' => 'peeliya', 'keywords' => 'jaundice,peeliya,yellow eyes', 'ai_hint' => 'jaundice with urine/stool color changes'],
            ['Code' => 34, 'Name' => 'BURNING EPIGASTRIUM', 'show_in_short' => 0, 'name_hinglish' => 'pet mein jalan', 'keywords' => 'epigastric burning,acidity,pet mein jalan', 'ai_hint' => 'burning epigastric pain relation with food'],
            ['Code' => 35, 'Name' => 'MICTURETION WITH BURNING', 'show_in_short' => 1, 'name_hinglish' => 'peshab mein jalan', 'keywords' => 'burning micturition,peshab mein jalan,dysuria', 'ai_hint' => 'dysuria with urinary frequency'],
            ['Code' => 38, 'Name' => 'MICTURETION WITH INCREASED FREQUENCY', 'show_in_short' => 0, 'name_hinglish' => 'bar bar peshab', 'keywords' => 'urinary frequency,bar bar peshab', 'ai_hint' => 'increased frequency with urgency'],
            ['Code' => 40, 'Name' => 'HAEMATURIA', 'show_in_short' => 0, 'name_hinglish' => 'peshab mein khoon', 'keywords' => 'haematuria,blood in urine,peshab mein khoon', 'ai_hint' => 'hematuria with clots/pain'],
            ['Code' => 45, 'Name' => 'HEADACHE', 'show_in_short' => 1, 'name_hinglish' => 'sir dard', 'keywords' => 'headache,sir dard,head pain', 'ai_hint' => 'headache with site/severity/photophobia'],
            ['Code' => 47, 'Name' => 'GIDDINESS', 'show_in_short' => 1, 'name_hinglish' => 'chakkar', 'keywords' => 'giddiness,chakkar,dizziness', 'ai_hint' => 'giddiness with postural relation'],
            ['Code' => 48, 'Name' => 'VERTIGO', 'show_in_short' => 1, 'name_hinglish' => 'ghoomna sa lagna', 'keywords' => 'vertigo,room spinning,ghoomna', 'ai_hint' => 'vertigo with duration and triggers'],
            ['Code' => 53, 'Name' => 'SLEEP', 'show_in_short' => 0, 'name_hinglish' => 'neend ki dikkat', 'keywords' => 'sleep disturbance,insomnia,neend', 'ai_hint' => 'sleep disturbance pattern'],
            ['Code' => 56, 'Name' => 'WEAKNESS', 'show_in_short' => 1, 'name_hinglish' => 'kamzori', 'keywords' => 'weakness,kamzori,fatigue', 'ai_hint' => 'weakness generalized/focal with onset'],
            ['Code' => 58, 'Name' => 'PAIN IN ABDOMEN', 'show_in_short' => 1, 'name_hinglish' => 'pet dard', 'keywords' => 'abdominal pain,pet dard,pain abdomen', 'ai_hint' => 'abdominal pain with site and food relation'],
            ['Code' => 59, 'Name' => 'NAUSEA', 'show_in_short' => 1, 'name_hinglish' => 'mann ghabrana', 'keywords' => 'nausea,mann ghabrana', 'ai_hint' => 'nausea with vomiting relation'],
            ['Code' => 62, 'Name' => 'NASAL DISCHARGE', 'show_in_short' => 0, 'name_hinglish' => 'naak bahna', 'keywords' => 'nasal discharge,runny nose,naak bahna', 'ai_hint' => 'nasal discharge with color and duration'],
            ['Code' => 66, 'Name' => 'DRIPPING NOSE', 'show_in_short' => 0, 'name_hinglish' => 'naak se paani', 'keywords' => 'dripping nose,runny nose,naak se paani', 'ai_hint' => 'rhinorrhea details'],
            ['Code' => 77, 'Name' => 'WEIGHT LOSS', 'show_in_short' => 0, 'name_hinglish' => 'wazan kam hona', 'keywords' => 'weight loss,wazan kam', 'ai_hint' => 'weight loss over duration'],
            ['Code' => 84, 'Name' => 'SWEATING', 'show_in_short' => 0, 'name_hinglish' => 'pasina', 'keywords' => 'sweating,pasina,diaphoresis', 'ai_hint' => 'sweating pattern day/night'],
            ['Code' => 91, 'Name' => 'HEARTBURN', 'show_in_short' => 0, 'name_hinglish' => 'seene mein jalan', 'keywords' => 'heartburn,acidity,seene mein jalan', 'ai_hint' => 'heartburn and meal relation'],
            ['Code' => 103, 'Name' => 'URINE DARK', 'show_in_short' => 0, 'name_hinglish' => 'gaadha peshab', 'keywords' => 'dark urine,gaadha peshab', 'ai_hint' => 'dark urine with hydration status'],
            ['Code' => 107, 'Name' => 'WHEEZING', 'show_in_short' => 0, 'name_hinglish' => 'seeti jaisi saans', 'keywords' => 'wheezing,seeti saans', 'ai_hint' => 'wheeze with trigger and nocturnal symptoms'],
            ['Code' => 108, 'Name' => 'FATIGUE', 'show_in_short' => 0, 'name_hinglish' => 'thakan', 'keywords' => 'fatigue,thakan,tiredness', 'ai_hint' => 'fatigue with functional limitation'],
            ['Code' => 110, 'Name' => 'SKIN RASH', 'show_in_short' => 0, 'name_hinglish' => 'daane/chakate', 'keywords' => 'skin rash,daane,itching rash', 'ai_hint' => 'rash distribution and itching'],
            ['Code' => 119, 'Name' => 'ANEMIA', 'show_in_short' => 0, 'name_hinglish' => 'khoon ki kami', 'keywords' => 'anemia,khoon ki kami,pallor', 'ai_hint' => 'anemia related symptoms'],
            ['Code' => 122, 'Name' => 'DEHYDRATION', 'show_in_short' => 0, 'name_hinglish' => 'pani ki kami', 'keywords' => 'dehydration,pani ki kami,dry mouth', 'ai_hint' => 'dehydration signs and intake'],
            ['Code' => 123, 'Name' => 'BODYACHE', 'show_in_short' => 1, 'name_hinglish' => 'body pain', 'keywords' => 'bodyache,body pain,sharir dard', 'ai_hint' => 'generalized bodyache with fever relation'],
            ['Code' => 127, 'Name' => 'TIREDNESS', 'show_in_short' => 1, 'name_hinglish' => 'thakawat', 'keywords' => 'tiredness,thakawat,fatigue', 'ai_hint' => 'tiredness severity and impact'],
            ['Code' => 129, 'Name' => 'BACK PAIN', 'show_in_short' => 1, 'name_hinglish' => 'peeth dard', 'keywords' => 'back pain,peeth dard,kamar dard', 'ai_hint' => 'back pain with red flags'],
            ['Code' => 131, 'Name' => 'COUGH WITH YELLOW EXPECTORATION', 'show_in_short' => 1, 'name_hinglish' => 'peela balgam', 'keywords' => 'yellow sputum,peela balgam,productive cough', 'ai_hint' => 'sputum color and duration'],
            ['Code' => 132, 'Name' => 'COUGH WITH WHITE EXPECTORATION', 'show_in_short' => 1, 'name_hinglish' => 'safed balgam', 'keywords' => 'white sputum,safed balgam,cough', 'ai_hint' => 'sputum characteristics'],
            ['Code' => 133, 'Name' => 'FEVER, CONTINUOUS', 'show_in_short' => 1, 'name_hinglish' => 'lagatar bukhar', 'keywords' => 'continuous fever,lagatar bukhar', 'ai_hint' => 'continuous fever with duration'],
            ['Code' => 134, 'Name' => 'FEVER, INTERMITTENT', 'show_in_short' => 1, 'name_hinglish' => 'ruk ruk kar bukhar', 'keywords' => 'intermittent fever,ruk ruk kar bukhar', 'ai_hint' => 'intermittent fever spikes'],
            ['Code' => 135, 'Name' => 'FEVER, REMITTANT', 'show_in_short' => 1, 'name_hinglish' => 'utarta chadhta bukhar', 'keywords' => 'remittent fever,utarta chadhta bukhar', 'ai_hint' => 'remittent fever pattern'],
            ['Code' => 137, 'Name' => 'WEIGHT GAIN', 'show_in_short' => 0, 'name_hinglish' => 'wazan badhna', 'keywords' => 'weight gain,wazan badhna', 'ai_hint' => 'weight gain trend'],
            ['Code' => 144, 'Name' => 'DIZZINESS', 'show_in_short' => 1, 'name_hinglish' => 'chakkar aana', 'keywords' => 'dizziness,chakkar,giddiness', 'ai_hint' => 'dizziness trigger and duration'],
            ['Code' => 164, 'Name' => 'BURNING MICTURETION', 'show_in_short' => 1, 'name_hinglish' => 'peshab mein jalan', 'keywords' => 'burning micturition,dysuria,peshab mein jalan', 'ai_hint' => 'dysuria with urgency/frequency'],
            ['Code' => 185, 'Name' => 'ITCHING', 'show_in_short' => 0, 'name_hinglish' => 'khujli', 'keywords' => 'itching,khujli,pruritus', 'ai_hint' => 'itching site and duration'],
            ['Code' => 190, 'Name' => 'THROAT PAIN', 'show_in_short' => 0, 'name_hinglish' => 'gale mein dard', 'keywords' => 'throat pain,sore throat,gale mein dard', 'ai_hint' => 'sore throat with fever/cough'],
            ['Code' => 191, 'Name' => 'SNEEZING', 'show_in_short' => 0, 'name_hinglish' => 'chheenk', 'keywords' => 'sneezing,chheenk,allergy', 'ai_hint' => 'sneezing with allergen exposure'],
            ['Code' => 205, 'Name' => 'BLOOD IN STOOL', 'show_in_short' => 1, 'name_hinglish' => 'stool mein khoon', 'keywords' => 'blood in stool,stool mein khoon', 'ai_hint' => 'blood in stool quantity/frequency'],
            ['Code' => 227, 'Name' => 'IRRITABILITY', 'show_in_short' => 0, 'name_hinglish' => 'chidhchidhapan', 'keywords' => 'irritability,chidhchidhapan,mood changes', 'ai_hint' => 'behavioral symptoms with sleep relation'],
            ['Code' => 228, 'Name' => 'DEPRESSION', 'show_in_short' => 0, 'name_hinglish' => 'udasi', 'keywords' => 'depression,udasi,low mood', 'ai_hint' => 'low mood with duration and impact'],
            ['Code' => 232, 'Name' => 'IRRITATION', 'show_in_short' => 1, 'name_hinglish' => 'jalan/irritation', 'keywords' => 'irritation,jalan,burning sensation', 'ai_hint' => 'site specific irritation details'],
        ];

        $now = date('Y-m-d H:i:s');
        foreach ($rows as &$row) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
            $row['is_active'] = 1;
        }
        unset($row);

        if (! empty($rows)) {
            $existingCodes = $this->db->table('complaints_master')
                ->select('Code')
                ->get()
                ->getResultArray();

            $existingCodeMap = [];
            foreach ($existingCodes as $existing) {
                $existingCodeMap[(int) ($existing['Code'] ?? 0)] = true;
            }

            $newRows = array_values(array_filter(
                $rows,
                static fn(array $row): bool => ! isset($existingCodeMap[(int) ($row['Code'] ?? 0)])
            ));

            if (! empty($newRows)) {
                $this->db->table('complaints_master')->insertBatch($newRows);
            }
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('complaints_master', true);
    }
}
