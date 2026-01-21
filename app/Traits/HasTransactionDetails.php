<?php 

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

trait HasTransactionDetails
{
    public $playerBalance = null;
    public $inquiryStatus = 'N/A';
    public $inquiryResponse = 'N/A';

    /**
     * Common method to get the secure API headers
     */
    protected function getSecureHeaders(): array
    {
        return [
            'hashen' => env('BO_API_HASHEN'),
            'Accept' => 'application/json',
        ];
    }

    public function getBalance($playerId)
    {
        // SECURITY: Check if user has permission to view balances
        if (!Auth::user()?->can('view_player_balance')) {
            $this->playerBalance = "Unauthorized";
            return;
        }

        // LOCAL MOCK: Skip API calls in local environment
        if (app()->isLocal()) {
            $this->playerBalance = number_format(rand(1000, 5000), 2);
            return;
        }

        try {
            $url = config('app.env') === 'production' 
                ? env('BO_API_PROD_URL') 
                : env('BO_API_STAGE_URL');

            $response = Http::asForm()
                ->withHeaders($this->getSecureHeaders())
                ->post($url . "/api/player/balance", [
                    'player_id' => $playerId,
                ]);

            /** @var \Illuminate\Http\Client\Response $response */
            if ($response->successful()) {
                $data = $response->json();
                $this->playerBalance = ($data['code'] == '200') 
                    ? number_format($data['balance'], 2) 
                    : "Error: " . ($data['message'] ?? 'Unknown');
            } else {
                $this->playerBalance = "API Error: " . $response->status();
            }
        } catch (\Exception $e) {
            Log::error("Balance API Failure: " . $e->getMessage());
            $this->playerBalance = "Connection Error";
        }
    }

    public function checkTransactionStatus($roundId, $transactionId, $playerId)
    {
        // SECURITY: Ensure user is logged in
        abort_unless(Auth::check(), 403);

        // LOCAL MOCK: Skip API calls in local environment
        if (app()->isLocal()) {
            $this->inquiryStatus = "MOCK_200";
            $this->inquiryResponse = "Local Simulated Success for $transactionId";
            return;
        }

        try {
            $url = config('app.env') === 'production' 
                ? env('BO_API_PROD_URL') 
                : env('BO_API_STAGE_URL');
                // dd($url);
            $response = Http::asForm()
                ->withHeaders($this->getSecureHeaders())
                ->post($url . "/api/transaction/inquiry", [
                    "player_id" => $playerId,
                    "roundId" => $roundId,
                    "transactionId" => $transactionId,
                ]);

            /** @var \Illuminate\Http\Client\Response $response */
            if ($response->successful()) {
                $res = $response->json();
                $this->inquiryStatus = $res['code'] ?? 'Processed';
                $this->inquiryResponse =  $res['status'] ?? "Data retrieved successfully"; 
            } else {
                $this->inquiryStatus = "HTTP " . $response->status();
            }
            
        } catch (\Exception $e) {
            Log::critical("Transaction Inquiry Failure", [
                'user' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            $this->inquiryStatus = "System Error";
        }
    }

    public function fetchExtensionData($record)
    {
        $operator = DB::table('mwapiv2_main.operator')
            ->where('operator_id', $record->operator_id)
            ->first();

        $operator_table = "transaction_" . $operator->client_name;
        $dbExt = DB::connection('extension_read');

        $transactions = $dbExt->select("
            SELECT id, transaction_id, amount, round_id, provider_transaction_id pt_id, provider_round_id pr_id, 
            (CASE WHEN transaction_type = 1 THEN 'debit' WHEN transaction_type = 2 THEN 'credit' ELSE 'refund' END) as type, 
            CONVERT_TZ(created_at, '+00:00', '+08:00') as date,
            (CASE 
                WHEN transaction_status = 1 then 'SUCCESS_TRANSACTION' 
                WHEN transaction_status = 2 then 'TRANSACTION_FAILED_NOT_ENOUGH_BALANCE' 
                WHEN transaction_status = 3 then 'TRANSACTION_FAILED' 
                WHEN transaction_status = 4 then 'CLIENT_SERVER_ERROR' 
                WHEN transaction_status = 5 then 'ROUND_NOT_FOUND' 
                WHEN transaction_status = 6 then 'TRANSACTION_NOT_FOUND' 
                WHEN transaction_status = 7 then 'GAME_NOT_FOUND'
            END) as transaction_status 
            FROM mwapiv2_transactions.$operator_table 
            WHERE round_id = ?", [$record->round_id]);

        $totalRow = $dbExt->selectOne("
            SELECT SUM(sub_amount) as total FROM (
                SELECT SUM(amount) as sub_amount 
                FROM mwapiv2_transactions.$operator_table 
                WHERE round_id = ? AND transaction_type > 1 
                GROUP BY transaction_id, provider_transaction_id
            ) tbl", [$record->round_id]);

        return [
            'transactions' => $transactions,
            'total' => $totalRow->total ?? 0,
            'syncPayout' => (round($record->win, 2) != round(($totalRow->total ?? 0), 2)),
        ];
    }

    public function fetchByTransactionId($record)
    {

        $operator_table = "transaction_" . $record->client_name;
        $dbExt = DB::connection('extension_read');

        $transactions = $dbExt->select("
            SELECT id, transaction_id, amount, round_id, provider_transaction_id pt_id, provider_round_id pr_id, player_id,
            (CASE WHEN transaction_type = 1 THEN 'debit' WHEN transaction_type = 2 THEN 'credit' ELSE 'refund' END) as type, 
            CONVERT_TZ(created_at, '+00:00', '+08:00') as date,
            (CASE 
                WHEN transaction_status = 1 then 'SUCCESS_TRANSACTION' 
                WHEN transaction_status = 2 then 'TRANSACTION_FAILED_NOT_ENOUGH_BALANCE' 
                WHEN transaction_status = 3 then 'TRANSACTION_FAILED' 
                WHEN transaction_status = 4 then 'CLIENT_SERVER_ERROR' 
                WHEN transaction_status = 5 then 'ROUND_NOT_FOUND' 
                WHEN transaction_status = 6 then 'TRANSACTION_NOT_FOUND' 
                WHEN transaction_status = 7 then 'GAME_NOT_FOUND'
            END) as transaction_status 
            FROM mwapiv2_transactions.$operator_table 
            WHERE round_id = ?", [$record->round_id]);

        $totalRow = $dbExt->selectOne("
            SELECT SUM(sub_amount) as total FROM (
                SELECT SUM(amount) as sub_amount 
                FROM mwapiv2_transactions.$operator_table 
                WHERE round_id = ? AND transaction_type > 1 
                GROUP BY transaction_id, provider_transaction_id
            ) tbl", [$record->round_id]);
        $firstRow = !empty($transactions) ? $transactions[0] : null;

        return [
            'transactions' => $transactions,
            'total' => $totalRow->total ?? 0,
            'syncPayout' => (round($record->win, 2) != round(($totalRow->total ?? 0), 2)),
            'player_id' => $firstRow ? $firstRow->player_id : 'N/A'
        ];
    }

    public function syncAmount($roundId, $amount)
    {
        DB::connection('bo_aggreagate') 
            ->table('per_round')
            ->where('round_id', $roundId)
            ->update(['win' => $amount]);

        Notification::make()->title('Successfully Synced')->success()->send();
    }
}