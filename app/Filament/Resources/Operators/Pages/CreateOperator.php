<?php

namespace App\Filament\Resources\Operators\Pages;

use App\Filament\Resources\Operators\OperatorResource;
use App\Models\Client;
use App\Models\OAuthClients;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateOperator extends CreateRecord
{
    protected static string $resource = OperatorResource::class;

    protected function afterCreate(): void
    {
        $operator = $this->record;
        $data = $this->form->getRawState();

        // 1. Create Operator User
        $this->createRelatedUser($operator, 'operator', $data);

        $sharedSecret = Str::random(40);
        
        // Loop through the raw form data instead of the relationship collection
        if (!empty($data['brands'])) {
            foreach ($data['brands'] as $brandData) {
                
                // Find the Brand ID that Filament just created for this entry
                // We search by name and operator to get the auto-generated primary key
                $brand = \App\Models\Brand::where('operator_id', $operator->operator_id)
                    ->where('brand_name', $brandData['brand_name'])
                    ->first();

                if (!$brand) continue;

                // 2. Create Brand User
                $this->createRelatedUser($brand, 'brand', $brandData);

                $currencies = $brandData['temp_currencies'] ?? [];
                foreach ($currencies as $code) {
                    // 3. Create Client
                    $client = \App\Models\Client::create([
                        'operator_id'               => $operator->operator_id,
                        'brand_id'                  => $brand->brand_id, // Use the ID we just fetched
                        'client_name'               => strtoupper($brand->brand_name . '_' . $code),
                        'default_currency'          => $code,
                        'status_id'                 => 1,
                        'api_ver'                   => '2.0',
                        'player_details_url'        => $brandData['temp_player_url'] ?? null,
                        'fund_transfer_url'         => $brandData['temp_fund_url'] ?? null,
                        'transaction_checker_url'   => $brandData['temp_check_url'] ?? null,
                        'balance_url'               => $brandData['temp_player_url'] ?? 'https://www.tiger-games.com/blank',
                        'debit_credit_transfer_url' => $brandData['temp_fund_url'] ?? 'https://www.tiger-games.com/blank',
                    ]);

                    // 4. Create OAuth Record
                    \App\Models\OAuthClients::create([
                        'client_id'     => $client->client_id,
                        'client_secret' => $sharedSecret,
                    ]);
                }
            }
        }
        
        // 5. Create the database tables
        // $this->createOperatorDatabaseTables($operator->client_code);
    }

    protected function createRelatedUser($model, $type, $formData): void
    {
        $email = $formData[$type . '_email'] ?? null;
        $username = $formData[$type . '_username'] ?? null;
        $password = $formData[$type . '_password'] ?? null;

        // Skip if the user data wasn't filled out in the form
        if (!$email || !$username) return;

        User::create([
            'email'           => $email,
            'username'        => $username,
            'password_string' => $password, // Raw string as per your system
            'password'        => bcrypt($password),
            'user_type'       => $type,
            'operator_id'     => ($type === 'operator') ? $model->operator_id : $model->operator_id,
            'brand_id'        => ($type === 'brand') ? $model->brand_id : null,
            // If you have a client_id column in Users:
            'client_id'       => ($type === 'agent') ? $model->client_id : null,
        ]);
    }

    /**
     * Converts the Go CreateTransactionTable logic to Laravel/PHP
     * * @param string $tableName The client_code or operator identifier
     */
    public function createOperatorDatabaseTables(string $tableName): void
    {
        // 1. Base Transaction Table SQL
        $createTableSQL = "
            CREATE TABLE IF NOT EXISTS mwapiv2_transactions.transaction_{$tableName} (
                id bigint NOT NULL AUTO_INCREMENT,
                transaction_id varchar(100) NOT NULL,
                round_id varchar(100) NOT NULL,
                provider_transaction_id varchar(100) NOT NULL,
                provider_round_id varchar(100) DEFAULT NULL,
                amount decimal(13,4) DEFAULT NULL,
                transaction_type smallint DEFAULT NULL,
                round_completed tinyint NOT NULL DEFAULT '0',
                created_at timestamp NOT NULL,
                updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                operator_id int DEFAULT NULL,
                client_id int DEFAULT NULL,
                player_id int DEFAULT NULL,
                sub_provider_id int DEFAULT NULL,
                game_id int DEFAULT NULL,
                transaction_status varchar(45) DEFAULT NULL,
                freeroundid varchar(100) DEFAULT '',
                PRIMARY KEY (created_at, transaction_id),
                UNIQUE KEY id_transaction_id_provider_transaction_id_created_at (id, created_at),
                KEY transaction_id (transaction_id),
                KEY provider_transaction_id (provider_transaction_id),
                KEY created_at (created_at),
                KEY operatorpergames (operator_id, client_id, sub_provider_id, game_id, player_id),
                KEY transaction_type (transaction_type),
                KEY round_id (round_id),
                KEY client_idx (client_id),
                KEY operator_idx (operator_id),
                KEY player_id (player_id),
                KEY provider_idx (sub_provider_id),
                KEY game_idx (game_id),
                KEY round_id_op_cp_pl_idx (round_id, operator_id, client_id, player_id, sub_provider_id, game_id, created_at),
                KEY provider_round_id (provider_round_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";

        // 2. Base Wallet Table SQL
        $queryCreateWalletTable = "
            CREATE TABLE IF NOT EXISTS mwapiv2_transactions.wallet_{$tableName} (
                id bigint NOT NULL AUTO_INCREMENT,
                transaction_id varchar(100) DEFAULT NULL,
                balance_before decimal(10,0) DEFAULT NULL,
                balance_after decimal(10,0) DEFAULT NULL,
                created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                status tinyint DEFAULT NULL,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";

        // 3. Generate Partition SQL for the next 7 days
        $partitions = [];
        $now = Carbon::now();

        for ($i = 0; $i < 7; $i++) {
            // Equivalent to date := currentDate.AddDate(0, 0, i+1)
            $targetDate = $now->copy()->addDays($i + 1);
            $partitionName = "p" . $targetDate->format('Ymd');
            
            // Equivalent to partitionDateValue := currentDate.AddDate(0, 0, i+2)
            $lessThanDate = $now->copy()->addDays($i + 2)->format('Y-m-d 00:00:00');
            
            $partitions[] = "PARTITION {$partitionName} VALUES LESS THAN (unix_timestamp('{$lessThanDate}')) ENGINE = InnoDB";
        }

        $partitionSQL = implode(",\n", $partitions);

        // 4. Finalize Transaction SQL with Partitions
        $finalTransactionSQL = $createTableSQL . "\n/*!50100 PARTITION BY RANGE (unix_timestamp(created_at)) (\n" . $partitionSQL . "\n) */;";

        // 5. Execute within a transaction
        DB::transaction(function () use ($finalTransactionSQL, $queryCreateWalletTable) {
            DB::statement($finalTransactionSQL);
            DB::statement($queryCreateWalletTable);
        });
    }
}
