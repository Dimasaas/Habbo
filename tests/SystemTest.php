<?php

use HabboAPI\Entities\{Badge, Group, Habbo, Room};
use HabboAPI\HabboAPI;
use HabboAPI\HabboParser;
use PHPUnit\Framework\TestCase;

/**
 * Class SystemTest is used to test the API against the actual Habbo.com server
 * These
 */
class SystemTest extends TestCase
{
    /** @var HabboParser $parser */
    private static $parser;
    /** @var HabboAPI $api */
    private static $api;

    public static function setUpBeforeClass(): void
    {
        self::$parser = new HabboParser('com');
        self::$api = new HabboAPI(self::$parser);
    }

    public function testTheSystem(): void
    {
        // Don't run system tests on Travis
        if (getenv('TRAVIS') == "true") {
            $this->markTestSkipped('This test should not run if on Travis.');
        }

        /* Test getHabbo() */
        $habbo = self::$api->getHabbo("VladimirSartini");

        $this->assertEquals("hr-893-42.hd-180-14.ch-215-73.lg-270-66.sh-300-66.ha-1014", $habbo->getFigureString());
        $this->assertEquals("VladimirSartini", $habbo->getHabboName());
        $this->assertEquals("hhus-e6d805c82fcd7f4717f4bff5f9f437ae", $habbo->getId());
        $this->assertEquals("2013-10-15 16:58:03", $habbo->getMemberSince()->toDateTimeString());
        $this->assertEquals("Hocus pocus!", $habbo->getMotto());
        $this->assertEquals(true, $habbo->getProfileVisible());

        // Test SelectedBadges
        $badges = $habbo->getSelectedBadges();
        $exp_badges = array(
            array('code' => 'DITGO', 'name' => 'Ditch the Label 10k', 'description' => 'to celebrate 10,000 anti-bullying survey entries. whoop!'),
            array('code' => 'ACH_RegistrationDuration15', 'name' => '75 % True Habbo XV', 'description' => 'Be a member of the community for 913 days.'),
            array('code' => 'ACH_SafetyQuizGraduate1', 'name' => 'Level I Safety tips', 'description' => 'For passing the Safety quiz!'),
        );
        foreach ($badges as $i => $badge) {
            $this->assertInstanceOf('HabboAPI\Entities\Badge', $badge);
            $this->assertEquals($exp_badges[$i]['code'], $badge->getId());
            $this->assertEquals($i + 1, $badge->getBadgeIndex());
            $this->assertEquals($exp_badges[$i]['code'], $badge->getCode());
            $this->assertEquals($exp_badges[$i]['description'], $badge->getDescription());
            $this->assertEquals($exp_badges[$i]['name'], $badge->getName());
        }

        /* Test getProfile() */
        $profile = self::$api->getProfile('hhus-e6d805c82fcd7f4717f4bff5f9f437ae');

        // Test a badge
        $badge = null;
        $badges = $profile->getBadges();
        foreach ($badges as $b) {
            if ($b->getCode() == "ACH_RespectEarned1") {
                $badge = $b;
            }
        }
        $this->assertEquals("ACH_RespectEarned1", $badge->getCode());
        $this->assertEquals("Respected Habbo I", $badge->getName());
        $this->assertEquals("For earning respect 1 time.", $badge->getDescription());

        // Test a group
        $groups = $profile->getGroups();
        $group = $groups[0];
        $this->assertEquals('g-hhus-7869307be6889988e1c51595981caeb4', $group->getId());
        $this->assertEquals('#HabboPlayNice', $group->getName());
        $this->assertEquals('Welcome to Serenity Garden, where we\'re all  about being buddies, not bullies. Come join us:)', $group->getDescription());
        $this->assertEquals('b21164s3308494f160c3e77fad248afac9925c56b2ed', $group->getBadgeCode());
        $this->assertEquals('f334bf', $group->getPrimaryColor());
        $this->assertEquals('c2eaff', $group->getSecondaryColor());
        $this->assertEquals('r-hhus-a70d8eb7be50f8a8e2852ab57c1e320e', $group->getRoomId());
        $this->assertEquals('NORMAL', $group->getType());

        // Test a room
        $rooms = $profile->getRooms();
        $room = $rooms[0];
        $categories = $room->getCategories(); // tmp var for php 5.3
        $this->assertEquals('r-hhus-7c58e2a91120887a52f3916b13085f19', $room->getId());
        $this->assertEquals('Reading', $room->getName());
        $this->assertEquals('A ghust of wind, whispering voices.', $room->getDescription());
        $this->assertEquals('navigator.flatcategory.global.HELP', $categories[0]);
        $this->assertEquals('25', $room->getMaximumVisitors());
        $this->assertEquals('hhus-e6d805c82fcd7f4717f4bff5f9f437ae', $room->getOwnerUniqueId());
        $this->assertEquals("2013-10-15 16:58:03", $room->getCreationTime()->toDateTimeString());

        // Test the counts
        $counts = $profile->getCounts();
        $this->assertEquals(1, $counts['habbo']);
        $this->assertEquals(15, $counts['badges']);
        $this->assertEquals(0, $counts['friends']); // Habbo deleted my only friend! :(
        $this->assertEquals(1, $counts['groups']);
        $this->assertEquals(2, $counts['rooms']);

        // Test Achievements
        $achievements = self::$api->getAchievements($habbo->getId());

        $found = false;
        foreach ($achievements as $ach) {
            if ($ach->getId() == 51) {
                $found = true;
                $this->assertEquals("RoomDecoFurniTypeCount", $ach->getName());
                $this->assertEquals("room_builder", $ach->getCategory());

                $this->assertEquals(9, $ach->getScore());
                $this->assertEquals(0, $ach->getLevel());

                break;
            }
        }

        $this->assertTrue($found);
    }

}
