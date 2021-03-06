<?php

namespace carlescliment\Quinieleitor;

use carlescliment\Quinieleitor\BetterSlip,
    carlescliment\Quinieleitor\ResultsSlip,
    carlescliment\Quinieleitor\BetterSlips,
    carlescliment\Quinieleitor\Score\PrizeCalculator,
    carlescliment\Quinieleitor\Match,
    carlescliment\Quinieleitor\Bet;

class BetterSlipsTest extends \PHPUnit_Framework_TestCase
{
    function setUp()
    {
        $prize_table = array(
            'prizes' => array(
                10 => 0.4,
                9 => 0.2,
                8 => 0.12,
                7 => 0.08,
            ),
            'slip_value' => 100,
        );
        $this->calculator = new PrizeCalculator($prize_table);
        $this->resultSlip = $this->createResultSlip(array(
            1 => '2',
            2 => '2',
            3 => '2',
            4 => '2',
            5 => '2',
            6 => '2',
            7 => '2',
            8 => '2',
            9 => '2',
            10 => '2',
        ));
    }


    function testParticipantHasNoPrice()
    {
        // Arrange
        $slips = new BetterSlips(array(
            $this->createBetterSlipWithMatches(0),
        ));

        // Act
        $slips->calculatePrizes($this->resultSlip, $this->calculator);

        // Assert
        $slip_items = $slips->all();
        $this->assertEquals(0, $slip_items[0]->getPrize());
    }


    function testABetterWasRewarded()
    {
        // Arrange
        $expected_prize = 8;
        $slips = new BetterSlips(array(
            $this->createBetterSlipWithMatches(7),
        ));

        // Act
        $slips->calculatePrizes($this->resultSlip, $this->calculator);

        // Assert
        $slip_items = $slips->all();
        $this->assertEquals($expected_prize, $slip_items[0]->getPrize(), sprintf('%d matches %d', $slip_items[0]->getPrize(), $expected_prize));
    }


    function testTwoBettersAndOnlyOneWasRewarded()
    {
        // Arrange
        $expected_first_prize = 16;
        $expected_second_prize = 0;
        $slips = new BetterSlips(array(
            $this->createBetterSlipWithMatches(7),
            $this->createBetterSlipWithMatches(0),
        ));

        // Act
        $slips->calculatePrizes($this->resultSlip, $this->calculator);

        // Assert
        $slip_items = $slips->all();
        $this->assertEquals($expected_first_prize, $slip_items[0]->getPrize(), sprintf('%d matches %d', $slip_items[0]->getPrize(), $expected_first_prize));
        $this->assertEquals($expected_second_prize, $slip_items[1]->getPrize(), sprintf('%d matches %d', $slip_items[1]->getPrize(), $expected_second_prize));
    }


    function testTwoBettersWithDifferentNumberOfHitsEarnTheFullReward()
    {
        // Arrange
        $expected_first_prize = 16;
        $expected_second_prize = 80;
        $slips = new BetterSlips(array(
            $this->createBetterSlipWithMatches(7),
            $this->createBetterSlipWithMatches(10),
        ));

        // Act
        $slips->calculatePrizes($this->resultSlip, $this->calculator);

        // Assert
        $slip_items = $slips->all();
        $this->assertEquals($expected_first_prize, $slip_items[0]->getPrize(), sprintf('%d matches %d', $slip_items[0]->getPrize(), $expected_first_prize));
        $this->assertEquals($expected_second_prize, $slip_items[1]->getPrize(), sprintf('%d matches %d', $slip_items[1]->getPrize(), $expected_second_prize));
    }


    function testTwoBettersWithTheSameNumberOfHitsShareTheReward()
    {
        // Arrange
        $expected_first_prize = 40;
        $expected_second_prize = 40;
        $slips = new BetterSlips(array(
            $this->createBetterSlipWithMatches(10),
            $this->createBetterSlipWithMatches(10),
        ));

        // Act
        $slips->calculatePrizes($this->resultSlip, $this->calculator);

        // Assert
        $slip_items = $slips->all();
        $this->assertEquals($expected_first_prize, $slip_items[0]->getPrize(), sprintf('%d matches %d', $slip_items[0]->getPrize(), $expected_first_prize));
        $this->assertEquals($expected_second_prize, $slip_items[1]->getPrize(), sprintf('%d matches %d', $slip_items[1]->getPrize(), $expected_second_prize));
    }


    function testAllTheVeryUnluckyBettersShareTheMaxPrize()
    {
        // Arrange
        $expected_shared_prize = 40;
        $slips = new BetterSlips(array(
            $this->createBetterSlipWithMatches(10),
            $this->createBetterSlipWithMatches(10),
            $this->createBetterSlipWithMatches(10),
            $this->createBetterSlipWithMatches(10),
            $this->createBetterSlipWithMatches(10),
            $this->createBetterSlipWithMatches(10),
            $this->createBetterSlipWithMatches(10),
            $this->createBetterSlipWithMatches(10),
            $this->createBetterSlipWithMatches(10),
            $this->createBetterSlipWithMatches(10),
        ));

        // Act
        $slips->calculatePrizes($this->resultSlip, $this->calculator);

        // Assert
        foreach ($slips->all() as $slip) {
            $this->assertEquals($expected_shared_prize, $slip->getPrize(), sprintf('%d matches %d', $slip->getPrize(), $expected_shared_prize));
        }
    }


    private function createBetterSlipWithMatches($num_matches)
    {
        $matches = array();
        $i = 1;
        while ($i <= $num_matches) {
            $matches[$i] = '2';
            $i++;
        }
        while ($i <= ResultsSlip::MATCHES_PER_SLIP) {
            $matches[$i] = '1';
            $i++;
        }

        return $this->createBetterSlip($matches);
    }


    private function createBetterSlip($matches)
    {
        $user_id = 40;
        $slip_id = 1;
        $slip = new BetterSlip($user_id, $slip_id);
        foreach ($matches as $match_id => $prediction) {
            $bet = new Bet(null, $match_id, $prediction);
            $slip->add($bet);
        }

        return $slip;
    }


    private function createResultSlip($matches)
    {
        $slip = new ResultsSlip(null, new \DateTime());
        foreach ($matches as $match_id => $result) {
            $match = new Match($match_id, "Match $match_id", $result);
            $slip->add($match);
        }

        return $slip;
    }
}
