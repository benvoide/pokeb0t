<?php

use Illuminate\Database\Seeder;

class TeamsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('teams')
            ->insert(
                [
                    [
                        'code'      => 'ATL',
                        'hashtag'   => '#TrueToAtlanta',
                    ],
                    [
                        'code'      => 'BKN',
                        'hashtag'   => '#WeGoHard',
                    ],
                    [
                        'code'      => 'BOS',
                        'hashtag'   => '#Celtics'
                    ],
                    [
                        'code'      => 'CHA',
                        'hashtag'   => '#AllFly'
                    ],
                    [
                        'code'      => 'CHI',
                        'hashtag'   => '#BullsNation'
                    ],
                    [
                        'code'      => 'CLE',
                        'hashtag'   => '#BeTheFight'
                    ],
                    [
                        'code'      => 'DAL',
                        'hashtag'   => '#MFFL'
                    ],
                    [
                        'code'      => 'DEN',
                        'hashtag'   => '#MileHighBasketball'
                    ],
                    [
                        'code'      => 'DET',
                        'hashtag'   => '#DetroitBasketball'
                    ],
                    [
                        'code'      => 'GSW',
                        'hashtag'   => '#DubNation'
                    ],
                    [
                        'code'      => 'HOU',
                        'hashtag'   => '#OneMission'
                    ],
                    [
                        'code'      => 'IND',
                        'hashtag'   => '#IndianaStyle'
                    ],
                    [
                        'code'      => 'LAC',
                        'hashtag'   => '#ClipperNation'
                    ],
                    [
                        'code'      => 'LAL',
                        'hashtag'   => '#LakeShow'
                    ],
                    [
                        'code'      => 'MEM',
                        'hashtag'   => '#GrindCity'
                    ],
                    [
                        'code'      => 'MIA',
                        'hashtag'   => '#HEATTwitter'
                    ],
                    [
                        'code'      => 'MIL',
                        'hashtag'   => '#FearTheDeer'
                    ],
                    [
                        'code'      => 'MIN',
                        'hashtag'   => '#Timberwolves'
                    ],
                    [
                        'code'      => 'NOP',
                        'hashtag'   => '#WontBowDown'
                    ],
                    [
                        'code'      => 'NYK',
                        'hashtag'   => '#NewYorkForever'
                    ],
                    [
                        'code'      => 'OKC',
                        'hashtag'   => '#ThunderUp'
                    ],
                    [
                        'code'      => 'ORL',
                        'hashtag'   => '#MagicAboveAll'
                    ],
                    [
                        'code'      => 'PHI',
                        'hashtag'   => '#PhilaUnite'
                    ],
                    [
                        'code'      => 'PHX',
                        'hashtag'   => '#RisePHX'
                    ],
                    [
                        'code'      => 'POR',
                        'hashtag'   => '#RipCity'
                    ],
                    [
                        'code'      => 'SAC',
                        'hashtag'   => '#SacramentoProud'
                    ],
                    [
                        'code'      => 'SAS',
                        'hashtag'   => '#GoSpursGo'
                    ],
                    [
                        'code'      => 'TOR',
                        'hashtag'   => '#WeTheNorth'
                    ],
                    [
                        'code'      => 'UTA',
                        'hashtag'   => '#TakeNote'
                    ],
                    [
                        'code'      => 'WAS',
                        'hashtag'   => '#RepTheDistrict'
                    ]
                ]
            );
    }
}
