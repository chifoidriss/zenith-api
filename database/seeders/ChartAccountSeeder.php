<?php

namespace Database\Seeders;

use App\Models\AccountType;
use App\Models\ChartAccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ChartAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $spreadsheet  = IOFactory::load(storage_path('files/accounts.xlsx'));

        $sheet        = $spreadsheet->getSheetByName('Sheet1');
        $row_limit    = $sheet->getHighestDataRow();
        $row_range    = range(2, $row_limit);
        foreach ($row_range as $row) {
            $code = $sheet->getCell('A' . $row)->getValue();
            $name = ucfirst($sheet->getCell('B' . $row)->getValue());
            $type = ucfirst($sheet->getCell('C' . $row)->getValue());
            $allow_reconciliation = $sheet->getCell('D' . $row)->getValue() == 'VRAI';

            $accountType = AccountType::firstWhere([
                'name' => $type
            ]);

            if (!$accountType) {
                $accountType = AccountType::create([
                    'name' => $type,
                    'role' => 'main'
                ]);
            }

            ChartAccount::create([
                'code' => $code,
                'name' => $name,
                'account_type_id' => $accountType->id,
                'allow_reconciliation' => $allow_reconciliation,
            ]);
        }
    }
}
