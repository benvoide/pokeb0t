<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Game;
use App\Team;
use Twitter;
use DateTime;
use DateTimeZone;
use DateInterval;
ini_set('xdebug.var_display_max_depth', '10');
ini_set('xdebug.var_display_max_children', '256');
ini_set('xdebug.var_display_max_data', '1024');

class BotController extends Controller
{

    private $today_data;

    private $today_games;

    private $timezones;

    /**
     * Instantiate a new UserController instance.
     */
    public function __construct() {
        $nba_api_endpoint_root  = function_exists( 'env' ) ? env( 'NBA_API_ENDPOINT_ROOT' , '' ) : '';
        $nba_api_endpoint_today = function_exists( 'env' ) ? env( 'NBA_API_ENDPOINT_TODAY' , '' ) : '';

        $response = $this->_handleCall( 'GET', $nba_api_endpoint_today );
        if ( !empty( $response ) ) {
            $endpoint = $nba_api_endpoint_root . $response->links->todayScoreboard;
            //var_dump($endpoint); die;
            //$endpoint = 'https://data.nba.net/10s/prod/v1/20200106/scoreboard.json';
            $response = $this->_handleCall( 'GET', $endpoint );
            if ( !empty( $response ) ) {
                $this->today_data = $response;
                $this->today_games = $response->games;
            }
        }
    }

    public function index() {
        if ( empty( $this->today_games ) ) {
            Twitter::postTweet(
                [
                    'status' => __( 'messages.nogames' ),
                    'format' => 'json'
                ]
            );
        } else {
            $games_from_db = 
                Game::where( 'game_date', date( 'Ymd')  )
                ->orWhere( 'game_date', date( 'Ymd', time() - 60 * 60 * 24))
                ->where( 'game_is_tweeted', 0)
                ->orWhere( 'game_finish_is_tweeted', 0)
                ->get();
            //var_dump($games_from_db); die;
            //die;
            if ( empty( $games_from_db->count() ) ) {
                foreach ( $this->today_games as $game ) {
                    $game_from_db = Game::where( 'game_id', $game->gameId )->first();
                    if ( empty( $game_from_db ) ) {
                        $game_to_save               = new Game;
                        $game_to_save->game_id      = $game->gameId;
                        $game_to_save->game_date    = date( 'Ymd' );
                        $game_to_save->save();
                    }
                }
            } else {
                foreach ( $this->today_games as $game ) {
                    //var_dump( 'aaabbbccc' ); die;
                    $game_from_db = Game::where( 'game_id', $game->gameId )->first();
                    if ( empty( $game_from_db->game_is_tweeted ) ) {
                        $visitor_team   = Team::where( 'code', $game->vTeam->triCode )->first();
                        $home_team      = Team::where( 'code', $game->hTeam->triCode )->first();

                        $result = $this->_calculateTime($game);
                        $translate_game_message = 'messages.game_message' . rand(1, 4);
                        $status =
                            __(
                                $translate_game_message,
                                [
                                    'vTeam' => $visitor_team->hashtag,
                                    'hTeam' => $home_team->hashtag
                                ]
                            )
                            . chr(13) . chr(10) .
                            '🇦🇷 🇺🇾 ' . $result[0]
                            . chr(13) . chr(10) .
                            '🇪🇸 ' .  $result[1]
                            . chr(13) . chr(10) .
                            '🇲🇽 ' . $result[2]
                            . chr(13) . chr(10) .
                            chr(13) . chr(10) .
                            '📺 https://watch.nba.com/game/' . $game->gameUrlCode .
                            chr(13) . chr(10).
                            '#NBA #NBATwitter @NBALatam @NBAspain @NBAMEX';

                        
                        Twitter::postTweet(
                            [
                                'status' => $status,
                                'format' => 'json'
                            ]
                        );
                        

                        //var_dump('aaa'); die;

                        $game_from_db->game_is_tweeted = TRUE;
                        $game_from_db->save();

                    } else {
                        //var_dump('thix'); die;
                        //var_dump( $game ); die;

                        //$endpoint = $endpoint = $nba_api_endpoint_root . $today_data->links->todayScoreboard;
                        
                        //var_dump();
                        if ( empty( $game_from_db->game_finish_is_tweeted ) ) {
                            var_dump( $game );
                            if ( !empty( $game->endTimeUTC ) ) {
                            	$endpoint = $this->_createBoxScoreEndpoint( $game );
                            	$response = $this->_handleCall( 'GET', $endpoint );
                                //var_dump( $endpoint ); die;
                            	//echo '<pre>' .
                                //echo '</pre>'; die;
                                //var_dump($game); die;
                                //if ($game->vTeam->score > )vTeam
                                $win_team = ( $game->vTeam->score > $game->hTeam->score ) ? $game->vTeam : $game->hTeam;
                                $los_team = ( $game->vTeam->score < $game->hTeam->score ) ? $game->vTeam : $game->hTeam;
                                //var_dump($win_team->triCode, $los_team->triCode); die;
                                $win_team_data_from_db = Team::where( 'code', $win_team->triCode )->first();
                                $los_team_data_from_db = Team::where( 'code', $los_team->triCode )->first();
                                if ( !empty($response) ) {
                                	$win_team_stats = 
                                		( $response->stats->vTeam->totals->points > $response->stats->hTeam->totals->points ) ?
											$response->stats->vTeam :
											$response->stats->hTeam;
									$los_team_stats = 
                                		( $response->stats->vTeam->totals->points < $response->stats->hTeam->totals->points ) ?
											$response->stats->vTeam :
											$response->stats->hTeam;

									$game_desc = $this->_generateGameDesc( $win_team_stats, $los_team_stats );
                                }

                                //var_dump($win_team_stats->leaders, $los_team_stats->leaders); die;
                                $game_desc = ( !empty( $game_desc ) ) ? $game_desc . chr(13) . chr(10) : '';
                                $status =
                                    ' (' . $win_team->win . '-'.  $win_team->loss . ') ' . $win_team_data_from_db->hashtag . ' ' .
                                    $win_team->score . ' - ' .
                                    $los_team->score .  ' ' .
                                    $los_team_data_from_db->hashtag .  ' (' . $los_team->win . '-'.  $los_team->loss . ') ' .
                                    chr(13) . chr(10) . 
                                    chr(13) . chr(10) .
                                    $game_desc .
                                    chr(13) . chr(10) .
                                    '#NBA #NBATwitter @NBALatam @NBAspain @NBAMEX';
                                
                                //var_dump('xxx'); die;
                                Twitter::postTweet(
                                [
                                    'status' => $status,
                                    'format' => 'json'
                                ]
                            );
                                $game_from_db->game_finish_is_tweeted = TRUE;
                                $game_from_db->save();

                            }
                        }
                    }
                }
            }
        }
    }

    private function _handleCall( $type = 'GET', $endpoint = '' ) {
        $client = new \GuzzleHttp\Client();

        try {
            $response =
                $client->request(
                    $type,
                    $endpoint
                );

            if ( $response->getStatusCode() === 200 ) {
                return json_decode( $response->getBody()->getContents() );
            } else {
                return NULL;
            }
        } catch ( Exception $e ) {}
    }

    private function _calculateTime($game) {
        $result = array();

        $days_dias = array(
        'Monday'=>'Lunes',
        'Tuesday'=>'Martes',
        'Wednesday'=>'Miércoles',
        'Thursday'=>'Jueves',
        'Friday'=>'Viernes',
        'Saturday'=>'Sábado',
        'Sunday'=>'Domingo'
        );

        $time_zones = array(
            'America/Argentina/Buenos_Aires',
            'Europe/Madrid',
            'America/Mexico_City'
        );

        foreach ($time_zones as $time_zone) {
            $userTimezone = new DateTimeZone($time_zone);
            $gmtTimezone = new DateTimeZone('GMT');
            $myDateTime = new DateTime($game->startTimeUTC, $gmtTimezone);
            $offset = $userTimezone->getOffset($myDateTime);
            $myInterval=DateInterval::createFromDateString((string)$offset . 'seconds');
            $myDateTime->add($myInterval);
            $result[] = $days_dias[$myDateTime->format('l')] . ' - ' . $myDateTime->format('H:i') . 'hs';
        }

        return $result;
    }

    private function _createBoxScoreEndpoint( $game ) {
    	if ( !empty( $game ) ) {

    		//var_dump( $game );
    		$root = function_exists( 'env' ) ? env( 'NBA_API_ENDPOINT_ROOT' , '' ) : '';
    		$boxscore = function_exists( 'env' ) ? env( 'NBA_API_ENDPOINT_BOXSCORE' , '' ) : '';
    		//var_dump($game->startTimeUTC); die;
    		$gmtTimezone = new DateTimeZone('GMT');
            $myDateTime = new DateTime(substr($game->gameUrlCode, 0, strpos($game->gameUrlCode, '/')), $gmtTimezone);
    		$end = str_replace(
    			'{{gameId}}',
    			$game->gameId,
    			str_replace( '{{gameDate}}', $myDateTime->format( 'Ymd' ), $boxscore )
    		);

    		return $root . $end;

    	}
    }

    private function _generateGameDesc( $win_team, $los_team ) {
    	if ( !empty( $win_team ) && !empty( $los_team ) ) {
    		//ar_dump($win_team->leaders); die;
    		foreach ($win_team->leaders as $stat => $value) {
    				//var_dump( $stat, $value ); die;
    			//$key_players[$stat]['val'] = $win_team->leaders->$stat->value;
    			foreach ($win_team->leaders->$stat->players as $player) {
    				# code...
    				//var_dump($win_team->leaders->$stat->value); die;
    				$key_players[$player->personId]['first_name'] 		= $player->firstName;
    				$key_players[$player->personId]['last_name'] 		= $player->lastName;
    				$key_players[$player->personId]['stats'][$stat] 	= $win_team->leaders->$stat->value;
    			}
    		}

    		//var_dump($key_players); die;
    		$w_text = '';
    		
    		$players_messages = array();
    		//var_dump( $key_players ); die;
    		foreach ($key_players as $player_key => $player_value) {
    			//var_dump( $player_value['stats'] );
    			$stats_message = '';
    			foreach ($player_value['stats'] as $key => $value) {
    				$stats_message = $stats_message . ' ' . $value . ' ' . $key;
    				# code...
    			}
    			$players_messages[] =
    				$player_value['first_name'] . ' ' .
    				$player_value['last_name'] . ': ' . $stats_message;
    			# code...
    			//foreach ($variable as $key => $value) {
    				# code...
    			//}
    			//$players_text[] = $player_value['first_name'] . 

    		}
    		
    		foreach ($players_messages as $message) {
    			$w_text = $w_text . ', ' . $message;
    			# code...
    		}
    		$filtered_points = 
    		str_replace(
    			'points',
    			'PTS',
    			rtrim(ltrim($w_text, ', '))
    		);

    		$filtered_reb = 
    		str_replace(
    			'rebounds',
    			'REB',
    			$filtered_points
    		);

    		$final_w = 
    		str_replace(
    			'assists',
    			'AST',
    			$filtered_reb
    		);

    		//var_dump($final_w);
    		//var_dump( $los_team );
    		$key_players = array();
    		foreach ($los_team->leaders as $stat => $value) {
    				//var_dump( $stat, $value ); die;
    			//$key_players[$stat]['val'] = $los_team->leaders->$stat->value;
    			foreach ($los_team->leaders->$stat->players as $player) {
    				# code...
    				//var_dump($los_team->leaders->$stat->value); die;
    				$key_players[$player->personId]['first_name'] 		= $player->firstName;
    				$key_players[$player->personId]['last_name'] 		= $player->lastName;
    				$key_players[$player->personId]['stats'][$stat] 	= $los_team->leaders->$stat->value;
    			}
    		}

    		//var_dump($key_players); die;
    		$l_text = '';
    		
    		$players_messages = array();
    		//var_dump( $key_players ); die;
    		foreach ($key_players as $player_key => $player_value) {
    			//var_dump( $player_value['stats'] );
    			$stats_message = '';
    			foreach ($player_value['stats'] as $key => $value) {
    				$stats_message = $stats_message . ' ' . $value . ' ' . $key;
    				# code...
    			}
    			$players_messages[] =
    				$player_value['first_name'] . ' ' .
    				$player_value['last_name'] . ': ' . $stats_message;
    			# code...
    			//foreach ($variable as $key => $value) {
    				# code...
    			//}
    			//$players_text[] = $player_value['first_name'] . 

    		}
    		
    		foreach ($players_messages as $message) {
    			$l_text = $l_text . ', ' . $message;
    			# code...
    		}

    		$filtered_points = 
    		str_replace(
    			'points',
    			'PTS',
    			rtrim(ltrim($l_text, ', '))
    		);

    		$filtered_reb = 
    		str_replace(
    			'rebounds',
    			'REB',
    			$filtered_points
    		);

    		$final_l = 
    		str_replace(
    			'assists',
    			'AST',
    			$filtered_reb
    		);

    		//var_dump( $final_l);

    		//die;$

    		return $final_w . chr(13) . chr(10)  . $final_l;

    	}
    }
}
