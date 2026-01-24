<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/playerdetailsrequest', function (Request $request) {
    return response()->json([
        'playerdetailsresponse' => [
            'status' => [
                'code' => 200,
                'status' => 'OK',
                'message' => 'The request was successfully completed.'
            ],
            'accountid'    => '93626',
            'accountname'  => 'rian',
            'username'     => 'rian',
            'email'        => 'riandraft@gmail.com',
            'balance'      => 27.00,
            'currencycode' => 'USD',
            'country_code' => 'PH',
            'firstname'    => 'Rian',
            'lastname'     => 'Draft',
            'gender'       => 'male',
            'refreshtoken' => 'newdf38d568801ea79b00eb857adc362'
        ]
    ], 200);
});



Route::post('/fundtransferrequest', function (Request $request) {

    $fundInfo = data_get($request->all(), 'fundtransferrequest.fundinfo');

    $transactionType = $fundInfo['transactiontype'] ?? null;
    $transactionId   = $fundInfo['transactionId'] ?? null;

    /**
     * Simulate duplicate transaction
     * (example condition – adjust as needed)
     */
    $duplicateTransactionIds = [
        'BNGD4381116c227c4be5a8c53dfc401464791DBT', 
        'BNGDl6ed4aa9a2ae9163309fc453928bd981DBT'
    ];

    if (
        $transactionType === 'debit' &&
        in_array($transactionId, $duplicateTransactionIds)
    ) {
        return response()->json([
            'fundtransferresponse' => [
                'status' => [
                    'code' => 402,
                    'status' => 'DUPLICATE_TRANSACTION',
                    'message' => 'DUPLICATE_TRANSACTION',
                ],
                'balance' => 15.21,
                'currencycode' => 'USD',
            ],
        ], 200);
    }
    /**
     * Debit success response
     */
    if ($transactionType === 'debit') {
        return response()->json([
            'fundtransferresponse' => [
                'status' => [
                    'code' => 200,
                    'status' => 'SUCCESS',
                    'message' => 'Transaction successful.',
                ],
                'balance' => 2038.1,
                'currencycode' => 'USD',
            ],
        ], 200);
    }

    /**
     * Fallback (optional)
     */
    return response()->json([
        'fundtransferresponse' => [
            'status' => [
                'code' => 400,
                'status' => 'INVALID_TRANSACTION',
                'message' => 'Invalid transaction type',
            ],
        ],
    ], 400);
});
