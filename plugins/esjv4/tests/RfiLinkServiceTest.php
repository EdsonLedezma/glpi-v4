<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\Catalog;
use GlpiPlugin\Esjv4\RfiLinkService;

esjv4_assert_true(class_exists(RfiLinkService::class), 'RfiLinkService class must exist');

$repo = new class {
    public array $links = [];

    public function createTicketLink(array $link): int
    {
        $link['id'] = count($this->links) + 1;
        $this->links[] = $link;

        return $link['id'];
    }
};

$service = new RfiLinkService($repo);
$result = $service->linkExistingTicket(12, [
    'tickets_id' => '44',
    'scope' => 'activity',
    'impact' => 'blocking',
    'phase_id' => '3',
    'building_id' => '4',
    'activity_id' => '5',
]);

esjv4_assert_same(true, $result['ok'], 'RFI link succeeds for a valid ticket link');
esjv4_assert_same(44, $repo->links[0]['tickets_id'], 'RFI link stores ticket id');
esjv4_assert_same(12, $repo->links[0]['project_id'], 'RFI link stores project id');
esjv4_assert_same('activity', $repo->links[0]['scope'], 'RFI link stores scope');
esjv4_assert_same(Catalog::DEFAULT_RFI_IMPACT, $repo->links[0]['impact'], 'RFI link stores impact');
esjv4_assert_same('open', $repo->links[0]['status'], 'RFI link starts open');
esjv4_assert_same(5, $repo->links[0]['activity_id'], 'RFI link stores activity id');

$invalid = $service->linkExistingTicket(12, [
    'tickets_id' => '0',
    'scope' => 'bad',
    'impact' => 'bad',
]);

esjv4_assert_same(false, $invalid['ok'], 'Invalid RFI link fails');
esjv4_assert_true(isset($invalid['errors']['tickets_id']), 'Invalid RFI link reports ticket id error');
esjv4_assert_true(isset($invalid['errors']['scope']), 'Invalid RFI link reports scope error');
esjv4_assert_true(isset($invalid['errors']['impact']), 'Invalid RFI link reports impact error');
