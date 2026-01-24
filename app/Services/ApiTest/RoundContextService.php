<?php

namespace App\Services\ApiTest;

use Illuminate\Support\Facades\DB;

class RoundContextService
{
    /**
     * Executes the specific SQL query to gather all necessary API credentials
     * and routing information for a given round.
     */
    public static function load(string $roundId): array
    {
        $row = DB::selectOne("
            select 
                gs.game_name as gamename,
                gs.uni_game_code as gameid,
                gs.provider_name as providername,
                gs.provider_code as providercode,
                gs.username as player_username,
                gs.client_player_id,
                gs.player_token as token,
                gs.client_id,
                gs.default_currency as currencycode,
                gs.client_access_token as access_token,
                gs.client_api_key as api_key,
                gs.player_details_url,
                gs.fund_transfer_url,
                gs.transaction_checker_url as check_transfer_url,
                gs.fund_transfer_url as fund_transfer_all_url
            from bo_aggreagate.per_round pr
            inner join mwapiv2_main.players p on p.player_id = pr.player_id 
            inner join mwapiv2_main.gamesession gs 
                on gs.game_id = pr.game_id 
               and gs.player_id = pr.player_id 
            where pr.round_id = ?
            order by gs.created_at
            limit 1
        ", [$roundId]);

        if (! $row) {
            throw new \Exception('Round not found');
        }

        return (array) $row;
    }
}