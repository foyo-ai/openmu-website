<?php

namespace Database\Seeders;

use App\Models\Guide;
use Illuminate\Database\Seeder;

/**
 * Seeds the player guides (cẩm nang). Idempotent: upserts by slug so it runs on
 * every deploy without duplicating.
 *
 * Numbers come from the muss6 OpenMU Season 6 config:
 *  - wing recipes: ChaosMixes.cs
 *  - armor sets + drop levels + class flags: Armors.cs
 *  - wing→class mapping: Wings.cs
 *  - material drop sources: map/monster drop config (Icarus, Barracks of Balgass, global jewel group)
 * Editorial advice (stat builds, farming tips) is flagged in a .guide-note for GM review.
 * Item art lives in public/images/items/item_{group}_{number}_0.png.
 */
class GuideSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->guides() as $g) {
            Guide::updateOrCreate(
                ['slug' => $g['slug']],
                array_merge($g, ['author_account_id' => null]),
            );
        }
    }

    /** <img> tag for an item art file (item_{code}.png). */
    private function img(string $code, string $alt): string
    {
        return '<img src="/images/items/item_' . $code . '_0.png" alt="' . $alt . '" title="' . $alt . '">';
    }

    private function guides(): array
    {
        return array_merge(
            [$this->wingsOverview(), $this->wingsCrafting(), $this->statBuilds()],
            $this->classGuides(),
        );
    }

    // ---------------------------------------------------------------- Wings --

    private function wingsOverview(): array
    {
        // wing code map for inline art
        $w = fn (string $code, string $name) => $this->img($code, $name);
        $body = <<<HTML
<p>Wings (cánh) là trang bị quan trọng bậc nhất ở muss6: tăng sát thương, tăng khả năng hấp thụ sát thương (giảm damage nhận vào) và cho phép bay/di chuyển. Mỗi class có dòng wing riêng, chia làm <strong>3 cấp</strong>.</p>

<h2>Wing theo class</h2>
<p>Bảng dưới là dòng wing của từng class theo đúng cấu hình máy chủ muss6:</p>
<div class="table-responsive">
<table>
<thead><tr><th>Class</th><th>Cấp 1</th><th>Cấp 2</th><th>Cấp 3</th></tr></thead>
<tbody>
<tr><td>Dark Knight</td><td>{$w('12_2', 'Wings of Satan')} Satan</td><td>{$w('12_5', 'Wings of Dragon')} Dragon</td><td>{$w('12_36', 'Wing of Storm')} Storm</td></tr>
<tr><td>Dark Wizard</td><td>{$w('12_1', 'Wings of Heaven')} Heaven</td><td>{$w('12_4', 'Wings of Soul')} Soul</td><td>{$w('12_37', 'Wing of Eternal')} Eternal</td></tr>
<tr><td>Fairy Elf</td><td>{$w('12_0', 'Wings of Elf')} Elf</td><td>{$w('12_3', 'Wings of Spirits')} Spirits</td><td>{$w('12_38', 'Wing of Illusion')} Illusion</td></tr>
<tr><td>Magic Gladiator</td><td>{$w('12_2', 'Wings of Satan')} Heaven/Satan</td><td>{$w('12_6', 'Wings of Darkness')} Darkness</td><td>{$w('12_39', 'Wing of Ruin')} Ruin</td></tr>
<tr><td>Dark Lord</td><td>—</td><td>{$w('13_30', 'Cape of Lord')} Cape of Lord</td><td>{$w('12_40', 'Cape of Emperor')} Cape of Emperor</td></tr>
<tr><td>Summoner</td><td>{$w('12_41', 'Wings of Curse')} Curse</td><td>{$w('12_42', 'Wings of Despair')} Despair</td><td>{$w('12_43', 'Wing of Dimension')} Dimension</td></tr>
<tr><td>Rage Fighter</td><td>—</td><td>{$w('12_49', 'Cape of Fighter')} Cape of Fighter</td><td>{$w('12_50', 'Cape of Overrule')} Cape of Overrule</td></tr>
</tbody>
</table>
</div>

<div class="guide-note">
<strong>Lưu ý về cấu hình muss6:</strong> ở máy chủ này, Dark Wizard dùng <em>Wing of Eternal</em> còn Fairy Elf dùng <em>Wing of Illusion</em> ở cấp 3 (một số server khác đảo ngược hai wing này). Bảng trên lấy trực tiếp từ cấu hình game nên là chuẩn của muss6.
</div>

<h2>Sự khác biệt giữa các cấp</h2>
<ul>
<li><strong>Wing cấp 1</strong> — mở khoá sớm, tăng sát thương cơ bản.</li>
<li><strong>Wing cấp 2</strong> — mạnh hơn hẳn, có thể kèm dòng Luck / Excellent khi chế tạo. Mốc "đủ dùng" để đi Blood Castle, Devil Square, farm.</li>
<li><strong>Wing cấp 3</strong> — cao cấp nhất, phòng thủ và sát thương vượt trội, có 3 dòng option ngẫu nhiên. Mục tiêu cày cuốc lâu dài.</li>
<li><strong>Dark Lord & Rage Fighter</strong> không có wing cấp 1; hai class này bắt đầu bằng <em>Cape</em> (tương đương wing cấp 2) rồi lên thẳng Cape cấp 3.</li>
</ul>

<p>Muốn biết cách chế từng cấp wing (nguyên liệu, tỉ lệ, nơi tìm vật phẩm), xem bài <strong>Cách xoay Wings tại Chaos Machine</strong>.</p>
HTML;

        return [
            'slug' => 'tong-quan-wings', 'category' => 'wings', 'class_key' => null, 'icon' => 'dove',
            'title_vi' => 'Tổng quan Wings (cấp 1 → 3)', 'title_en' => 'Wings overview (level 1 → 3)',
            'excerpt_vi' => 'Dòng wing của từng class ở muss6 và khác biệt giữa 3 cấp.',
            'excerpt_en' => 'Each class\'s wing line on muss6 and how the 3 levels differ.',
            'body_vi' => $body, 'body_en' => null, 'sort_order' => 1, 'is_published' => true,
        ];
    }

    private function wingsCrafting(): array
    {
        $chaos = $this->img('12_15', 'Jewel of Chaos');
        $bless = $this->img('14_13', 'Jewel of Bless');
        $soul = $this->img('14_14', 'Jewel of Soul');
        $creation = $this->img('14_22', 'Jewel of Creation');
        $life = $this->img('14_16', 'Jewel of Life');
        $loch = $this->img('13_14', "Loch's Feather");
        $flame = $this->img('13_52', 'Flame of Condor');
        $condor = $this->img('13_53', 'Feather of Condor');

        $body = <<<HTML
<p>Wings được chế tạo tại <strong>Chaos Goblin Machine</strong> (NPC ở thành Noria). Bỏ đủ nguyên liệu vào máy, trả phí zen rồi bấm "Combine". Nếu thất bại, vật phẩm chính có thể tụt cấp hoặc biến mất — nên đọc kỹ trước khi làm.</p>

<div class="guide-note">
Các con số dưới đây (nguyên liệu, số ngọc, tỉ lệ, zen) và nguồn rơi lấy trực tiếp từ cấu hình máy chủ muss6. Nếu GM chỉnh lại công thức/drop trong admin, hãy cập nhật bài này.
</div>

<h2>Wing cấp 1</h2>
<ul>
<li><strong>Vật phẩm chính:</strong> 1 vũ khí Chaos (Chaos Dragon Axe / Chaos Nature Bow / Chaos Lightning Staff) +4 trở lên, có option.</li>
<li><strong>Tuỳ chọn:</strong> thêm 1 vật phẩm bất kỳ +4 trở lên (có option) để tăng tỉ lệ.</li>
<li><strong>Ngọc:</strong> 1 {$chaos} Jewel of Chaos (bắt buộc) + thêm {$bless} Bless / {$soul} Soul để tăng % thành công.</li>
<li><strong>Phí:</strong> ~10.000 zen cho mỗi 1% tỉ lệ.</li>
<li><strong>Kết quả (ngẫu nhiên):</strong> Wings of Elf / Heaven / Satan / Curse.</li>
</ul>

<h2>Wing cấp 2</h2>
<ul>
<li><strong>Vật phẩm chính:</strong> 1 wing cấp 1 bất kỳ (+0 → +15).</li>
<li><strong>Tuỳ chọn:</strong> thêm 1 vật phẩm <em>excellent</em> +4 trở lên để tăng tỉ lệ.</li>
<li><strong>Ngọc:</strong> 1 {$chaos} Jewel of Chaos + 1 {$loch} Loch's Feather (Lông vũ).</li>
<li><strong>Phí:</strong> 5.000.000 zen. <strong>Tỉ lệ tối đa 90%.</strong></li>
<li><strong>Kết quả (ngẫu nhiên):</strong> Wings of Spirits / Soul / Dragon / Darkness / Despair. Có 20% cơ hội ra kèm dòng Luck và 20% cơ hội kèm 1 dòng Excellent.</li>
</ul>

<h2>Wing cấp 3</h2>
<p>Wing cấp 3 làm qua <strong>2 bước</strong>.</p>
<h3>Bước 1 — Chế Feather of Condor</h3>
<ul>
<li>1 wing cấp 2 (hoặc Cape) +9 → +15, có option.</li>
<li>1 vật phẩm <strong>Ancient (đồ thần)</strong> +7 → +15, có ancient bonus + option.</li>
<li>1 {$chaos} Chaos + 1 {$creation} Creation + 1 Packed Jewel of Soul.</li>
<li>Phí ~200.000 zen mỗi 1%. Tỉ lệ 1% → <strong>tối đa 60%</strong>.</li>
<li>Thành công nhận {$condor} <strong>Feather of Condor</strong>.</li>
</ul>
<h3>Bước 2 — Chế Wing cấp 3</h3>
<ul>
<li>1 vật phẩm <em>excellent</em> +9 → +15.</li>
<li>1 {$condor} Feather of Condor + 1 {$flame} Flame of Condor.</li>
<li>1 {$chaos} Chaos + 1 {$creation} Creation + 1 Packed Jewel of Soul + 1 Packed Jewel of Bless.</li>
<li>Tỉ lệ <strong>tối đa 40%</strong>.</li>
<li>Kết quả: wing cấp 3 tương ứng class (hoặc Cape of Emperor / Cape of Overrule).</li>
</ul>

<h2>Nguyên liệu & nơi tìm</h2>
<div class="table-responsive">
<table>
<thead><tr><th></th><th>Nguyên liệu</th><th>Dùng cho</th><th>Nguồn rơi (theo config muss6)</th></tr></thead>
<tbody>
<tr><td>{$chaos}</td><td>Jewel of Chaos</td><td>Cả 3 cấp</td><td>Rơi từ mọi quái (~0.1%); Chaos Castle thưởng 90%; Red Dragon rơi 100%</td></tr>
<tr><td>{$bless}</td><td>Jewel of Bless</td><td>Cấp 1 (tăng %)</td><td>Rơi từ mọi quái (~0.1%); Chaos Castle; Red Dragon</td></tr>
<tr><td>{$soul}</td><td>Jewel of Soul</td><td>Cấp 1 (tăng %)</td><td>Rơi từ mọi quái (~0.1%); Chaos Castle; Red Dragon</td></tr>
<tr><td>{$creation}</td><td>Jewel of Creation</td><td>Cấp 3</td><td>Rơi từ mọi quái (~0.1%, quái lvl 72+); Chaos Castle</td></tr>
<tr><td>{$life}</td><td>Jewel of Life</td><td>Nâng cấp option</td><td>Rơi từ mọi quái (~0.1%, quái lvl 72+)</td></tr>
<tr><td>{$loch}</td><td>Loch's Feather</td><td>Cấp 2</td><td><strong>Chỉ map Icarus</strong> (~0.1%, quái lvl 82+)</td></tr>
<tr><td>{$flame}</td><td>Flame of Condor</td><td>Cấp 3 (bước 2)</td><td><strong>Chỉ map Barracks of Balgass</strong> (~0.1%)</td></tr>
<tr><td>{$condor}</td><td>Feather of Condor</td><td>Cấp 3</td><td>Không rơi — chỉ chế được ở bước 1</td></tr>
</tbody>
</table>
</div>
<div class="guide-note">
Ở muss6, ngọc rơi theo cơ chế <strong>toàn cục</strong>: mọi quái ở mọi map đều có ~0.1% rơi ngọc. Muốn cày ngọc nhanh, chọn map có mật độ quái cao và giết nhanh. Riêng Loch's Feather chỉ có ở <strong>Icarus</strong> và Flame of Condor chỉ có ở <strong>Barracks of Balgass</strong>.
</div>

<h2>Mẹo</h2>
<ul>
<li>Luôn cộng thêm ngọc / vật phẩm phụ để đẩy tỉ lệ lên cao nhất trước khi bấm ghép.</li>
<li>Packed Jewel = gộp 10 ngọc thường tại NPC đóng gói; wing cấp 3 cần các loại Packed.</li>
<li>Wing cấp 3 tỉ lệ thấp (≤40%) — chuẩn bị dư nguyên liệu, đừng nản khi thất bại.</li>
</ul>
HTML;

        return [
            'slug' => 'cach-xoay-wings', 'category' => 'wings', 'class_key' => null, 'icon' => 'gears',
            'title_vi' => 'Cách xoay Wings tại Chaos Machine', 'title_en' => 'How to craft wings at the Chaos Machine',
            'excerpt_vi' => 'Công thức chế wing cấp 1, 2, 3: nguyên liệu, tỉ lệ, zen và nơi tìm vật phẩm.',
            'excerpt_en' => 'Recipes for level 1/2/3 wings: materials, chances, zen and where to farm.',
            'body_vi' => $body, 'body_en' => null, 'sort_order' => 2, 'is_published' => true,
        ];
    }

    private function statBuilds(): array
    {
        $body = <<<'HTML'
<p>Mỗi khi lên cấp, nhân vật nhận điểm để cộng vào các chỉ số. Cộng đúng ngay từ đầu giúp bạn mạnh hơn và tiết kiệm reset. Ở muss6 bạn có thể reset điểm khi cần, nhưng hiểu nguyên tắc vẫn quan trọng.</p>

<h2>4 chỉ số cơ bản</h2>
<ul>
<li><strong>Strength (Sức mạnh)</strong> — tăng sát thương vật lý và yêu cầu để mặc đồ nặng. Cốt lõi của Dark Knight, Rage Fighter, một phần Magic Gladiator.</li>
<li><strong>Agility (Nhanh nhẹn)</strong> — tăng thủ, tốc đánh, sát thương cung. Cốt lõi của Fairy Elf; các class khác cộng đủ để mặc đồ.</li>
<li><strong>Vitality (Thể lực)</strong> — tăng máu (HP). Cần cho PvP và trụ lâu khi farm.</li>
<li><strong>Energy (Năng lượng)</strong> — tăng sát thương phép. Cốt lõi của Dark Wizard, Summoner, Magic Gladiator hệ phép.</li>
<li><strong>Command (Mệnh lệnh)</strong> — chỉ Dark Lord có, tăng sát thương thú cưỡi và triệu hồi.</li>
</ul>

<h2>Nguyên tắc cộng điểm hiệu quả</h2>
<ol>
<li><strong>Cộng đủ chỉ số phụ để mặc được đồ mục tiêu</strong>, phần còn lại dồn vào chỉ số sát thương chính. Đừng cộng thừa Agility/Strength quá mức yêu cầu đồ.</li>
<li><strong>Giai đoạn cày cấp</strong> (level thấp): ưu tiên chỉ số sát thương để giết quái nhanh.</li>
<li><strong>Giai đoạn PvP</strong> (sau reset, đồ tốt): dồn thêm Vitality để đủ máu sống trong giao tranh.</li>
<li>Ghi nhớ mốc chỉ số của bộ đồ bạn nhắm tới — xem trong hướng dẫn từng class.</li>
</ol>

<div class="guide-note">
Phần định hướng build ở đây và trong các trang class là kinh nghiệm theo Season 6. Tuỳ tỉ lệ và cấu hình reset của muss6, GM có thể tinh chỉnh lại cho hợp máy chủ.
</div>

<p>Chọn class của bạn ở mục <strong>Hướng dẫn theo class</strong> để xem build điểm chi tiết, set đồ và wing phù hợp.</p>
HTML;

        return [
            'slug' => 'cong-diem-cac-class', 'category' => 'stats', 'class_key' => null, 'icon' => 'chart-simple',
            'title_vi' => 'Cách cộng điểm hiệu quả', 'title_en' => 'Effective stat builds',
            'excerpt_vi' => 'Ý nghĩa 4 chỉ số và nguyên tắc cộng điểm chung cho mọi class.',
            'excerpt_en' => 'What the core stats do and general point-allocation principles.',
            'body_vi' => $body, 'body_en' => null, 'sort_order' => 1, 'is_published' => true,
        ];
    }

    // -------------------------------------------------------------- Classes --

    /** Builds a class-guide body from structured data (keeps all 7 consistent). */
    private function classBody(array $d): string
    {
        // gear tiers table
        $rows = '';
        foreach ($d['tiers'] as $tier => $sets) {
            $rows .= '<tr><td>' . $tier . '</td><td>' . implode(' → ', $sets) . '</td></tr>';
        }

        // wings strip (each: [code, name, level-label])
        $wingCards = '';
        foreach ($d['wings'] as $wg) {
            $wingCards .= '<li>' . $this->img($wg[0], $wg[1]) . ' <strong>' . $wg[1] . '</strong> — ' . $wg[2] . '</li>';
        }

        $ancient = <<<'HTML'
<div class="guide-note">
"Đồ thần" (Ancient) là <strong>hạng option</strong> của món đồ: cùng một bộ có thể tồn tại ở bản thường, Excellent, và Ancient (đồ thần) — bản Ancient có thêm chỉ số cổ và <strong>set bonus</strong> khi mặc đủ số món cùng bộ. Kiếm từ Kanturu, Land of Trials, hoặc chế qua Chaos Machine.
</div>
HTML;

        return <<<HTML
<p>{$d['intro']}</p>

<h2>Set đồ theo cấp độ</h2>
<p>Các bộ đồ {$d['name']} có thể mặc, xếp theo cấp độ rơi (drop level) — lấy từ cấu hình muss6:</p>
<div class="table-responsive">
<table>
<thead><tr><th>Giai đoạn</th><th>Bộ đồ (cấp độ rơi)</th></tr></thead>
<tbody>{$rows}</tbody>
</table>
</div>

<h3>Đồ thường, Excellent và Đồ thần</h3>
<ul>
<li><strong>Đồ thường</strong> — chỉ có phòng thủ cơ bản, dùng qua giai đoạn đầu.</li>
<li><strong>Đồ Excellent (đồ hoàng kim)</strong> — có dòng option excellent (hồi HP/MP khi đánh, tăng % sát thương, giảm damage nhận...). Rơi từ Blood Castle, hộp Kundun, boss.</li>
<li><strong>Đồ thần (Ancient)</strong> — thuộc một bộ Ancient, có chỉ số cổ + set bonus. "Đồ thần" người chơi hay nhắc tới chính là hạng này.</li>
</ul>
{$ancient}

<h2>Vũ khí phù hợp</h2>
<p>{$d['weapon']}</p>

<h2>Wings cho {$d['name']}</h2>
<ul class="mu-wing-list">{$wingCards}</ul>
<p>Cách chế chi tiết xem bài <strong>Cách xoay Wings tại Chaos Machine</strong>.</p>

<h2>Cộng điểm cho {$d['name']}</h2>
{$d['build']}
<div class="guide-note">
Build trên theo kinh nghiệm Season 6. GM nên rà lại theo tỉ lệ và cấu hình cụ thể của muss6.
</div>
HTML;
    }

    private function classGuides(): array
    {
        $defs = [
            [
                'slug' => 'dark-knight', 'class_key' => 'dark-knight', 'icon' => 'khanda', 'order' => 1,
                'title' => 'Dark Knight (Blade Knight)', 'name' => 'Dark Knight',
                'excerpt' => 'Set đồ, wings và cách cộng điểm cho Dark Knight.',
                'intro' => 'Dark Knight (chuyển sinh thành <strong>Blade Knight</strong>) là class cận chiến máu trâu, sát thương vật lý cao, dễ chơi — lựa chọn tốt cho người mới. Đánh gần, chịu đòn khoẻ, phù hợp cả cày cuốc lẫn PvP.',
                'weapon' => 'Kiếm, Rìu, Chuỳ (sát thương vật lý). Ưu tiên vũ khí Excellent có dòng tăng % sát thương và hồi máu khi đánh.',
                'tiers' => [
                    'Sơ cấp' => ['Leather (10)', 'Bronze (18)', 'Scale (28)'],
                    'Trung cấp' => ['Brass (38)', 'Plate (48)', 'Dragon (59)'],
                    'Cao cấp' => ['Ashcrow (75)', 'Black Dragon (90)', 'Dark Phoenix (100)'],
                    'Đỉnh cao' => ['Great Dragon (126)', 'Brave (128)', 'Titan (132)', 'Dragon Knight (140)'],
                ],
                'wings' => [['12_2', 'Wings of Satan', 'cấp 1'], ['12_5', 'Wings of Dragon', 'cấp 2 — mốc để farm hiệu quả'], ['12_36', 'Wing of Storm', 'cấp 3 — mục tiêu cuối']],
                'build' => '<ul><li><strong>Strength</strong> — sát thương chính, dồn nhiều nhất.</li><li><strong>Agility</strong> — đủ để mặc bộ đồ mục tiêu và đạt tốc đánh.</li><li><strong>Vitality</strong> — tăng dần khi lên đồ tốt, nhất là khi thiên PvP.</li><li><strong>Energy</strong> — hầu như không cần.</li></ul><p><em>Cày PvE:</em> tối đa Str, Agi vừa đủ đồ. <em>PvP:</em> Str cao + Vit khá.</p>',
            ],
            [
                'slug' => 'dark-wizard', 'class_key' => 'dark-wizard', 'icon' => 'hat-wizard', 'order' => 2,
                'title' => 'Dark Wizard (Soul Master)', 'name' => 'Dark Wizard',
                'excerpt' => 'Set đồ, wings và cách cộng điểm cho Dark Wizard.',
                'intro' => 'Dark Wizard (chuyển sinh thành <strong>Soul Master</strong>) là pháp sư tầm xa, sát thương phép diện rộng, dọn quái cực nhanh. Máu mỏng nên cần giữ khoảng cách và né đòn.',
                'weapon' => 'Gậy phép (Staff) và các Skill/Orb để học kỹ năng. Ưu tiên Staff Excellent tăng % sát thương phép.',
                'tiers' => [
                    'Sơ cấp' => ['Pad (10)', 'Bone (22)', 'Sphinx (38)'],
                    'Trung cấp' => ['Legendary (56)', 'Eclipse (75)'],
                    'Cao cấp' => ['Grand Soul (91)', 'Dark Soul (122)'],
                    'Đỉnh cao' => ['Hades (129)', 'Venom Mist (146)'],
                ],
                'wings' => [['12_1', 'Wings of Heaven', 'cấp 1'], ['12_4', 'Wings of Soul', 'cấp 2'], ['12_37', 'Wing of Eternal', 'cấp 3']],
                'build' => '<ul><li><strong>Energy</strong> — sát thương phép, dồn nhiều nhất.</li><li><strong>Vitality</strong> — thêm để sống lâu (máu Wizard rất mỏng).</li><li><strong>Agility/Strength</strong> — chỉ cộng đủ để mặc đồ.</li></ul><p><em>PvE:</em> tối đa Energy. <em>PvP:</em> Energy cao + Vit nhiều để chịu đòn.</p>',
            ],
            [
                'slug' => 'fairy-elf', 'class_key' => 'fairy-elf', 'icon' => 'bullseye', 'order' => 3,
                'title' => 'Fairy Elf (Muse Elf)', 'name' => 'Fairy Elf',
                'excerpt' => 'Set đồ, wings và cách cộng điểm cho Fairy Elf.',
                'intro' => 'Fairy Elf (chuyển sinh thành <strong>Muse Elf</strong>) linh hoạt: có thể build cung thủ sát thương tầm xa, hoặc build hỗ trợ (buff tăng sát thương/thủ cho cả team). Rất được săn đón khi đi party.',
                'weapon' => 'Cung và Nỏ (Crossbow). Build buff thì ưu tiên chỉ số Energy để buff mạnh; build sát thương thì Cung Excellent.',
                'tiers' => [
                    'Sơ cấp' => ['Vine (10)', 'Silk (20)', 'Wind (32)'],
                    'Trung cấp' => ['Spirit (44)', 'Guardian (57)', 'Iris (75)'],
                    'Cao cấp' => ['Divine (92)', 'Red Spirit (109)'],
                    'Đỉnh cao' => ['Faith (122)', 'Seraphim (129)', 'Sylphid Ray (146)'],
                ],
                'wings' => [['12_0', 'Wings of Elf', 'cấp 1'], ['12_3', 'Wings of Spirits', 'cấp 2'], ['12_38', 'Wing of Illusion', 'cấp 3']],
                'build' => '<ul><li><strong>Agility</strong> — sát thương cung + thủ, chỉ số chính của build cung thủ.</li><li><strong>Energy</strong> — cốt lõi nếu build hỗ trợ (buff mạnh hơn).</li><li><strong>Vitality</strong> — vừa phải để không quá giòn.</li></ul><p><em>Cung thủ:</em> tối đa Agility. <em>Support:</em> nhiều Energy + Agi đủ đồ.</p>',
            ],
            [
                'slug' => 'magic-gladiator', 'class_key' => 'magic-gladiator', 'icon' => 'meteor', 'order' => 4,
                'title' => 'Magic Gladiator', 'name' => 'Magic Gladiator',
                'excerpt' => 'Set đồ, wings và cách cộng điểm cho Magic Gladiator.',
                'intro' => 'Magic Gladiator là class lai giữa chiến binh và pháp sư, <strong>không cần chuyển sinh</strong> (chỉ 1 nhân vật). Nhận nhiều điểm chỉ số hơn mỗi cấp, mạnh ở mọi giai đoạn — nhưng không đội được Helm.',
                'weapon' => 'Kiếm, Giáo (vật lý) hoặc Gậy (phép). Chọn theo hướng build: full vật lý, full phép, hoặc lai.',
                'tiers' => [
                    'Sơ cấp' => ['Leather/Pad (10)', 'Bronze (18)', 'Scale (28)'],
                    'Trung cấp' => ['Brass (38)', 'Plate (48)', 'Dragon (59)'],
                    'Cao cấp' => ['Storm Crow (80)', 'Valiant (105)', 'Thunder Hawk (107)'],
                    'Đỉnh cao' => ['Hurricane (128)', 'Destroy (131)', 'Volcano (147)'],
                ],
                'wings' => [['12_2', 'Wings of Heaven/Satan', 'cấp 1'], ['12_6', 'Wings of Darkness', 'cấp 2'], ['12_39', 'Wing of Ruin', 'cấp 3']],
                'build' => '<ul><li><strong>Strength</strong> — hướng vật lý (phổ biến, dễ chơi).</li><li><strong>Energy</strong> — hướng phép, hoặc lai Str+Energy.</li><li><strong>Vitality</strong> — thêm để trụ khi PvP.</li></ul><p>Build vật lý dễ farm; build lai linh hoạt PvP nhưng cần nhiều đồ hơn.</p>',
            ],
            [
                'slug' => 'dark-lord', 'class_key' => 'dark-lord', 'icon' => 'crown', 'order' => 5,
                'title' => 'Dark Lord', 'name' => 'Dark Lord',
                'excerpt' => 'Set đồ, wings và cách cộng điểm cho Dark Lord.',
                'intro' => 'Dark Lord là class "chúa tể": có <strong>thú cưỡi</strong> (ngựa/quạ), buff cho đồng đội, và đòn AoE mạnh. Chỉ huy party rất tốt, sống dai nhờ đi cùng pet.',
                'weapon' => 'Quyền trượng (Scepter) + Khiên, cùng Dark Raven (quạ) hỗ trợ sát thương. Scepter Excellent tăng sát thương pet.',
                'tiers' => [
                    'Sơ cấp' => ['Leather (10)', 'Bronze (18)', 'Scale (28)'],
                    'Trung cấp' => ['Light Plate (62)', 'Adamantine (78)'],
                    'Cao cấp' => ['Dark Steel (96)', 'Glorious (105)', 'Dark Master (117)'],
                    'Đỉnh cao' => ['Sunlight (147)'],
                ],
                'wings' => [['13_30', 'Cape of Lord', 'cấp 2 (Dark Lord không có wing cấp 1)'], ['12_40', 'Cape of Emperor', 'cấp 3']],
                'build' => '<ul><li><strong>Command (Mệnh lệnh)</strong> — chỉ số riêng của Dark Lord, tăng sát thương thú cưỡi/triệu hồi, dồn nhiều.</li><li><strong>Strength/Vitality</strong> — để trụ và sát thương bản thân.</li><li><strong>Energy</strong> — tuỳ kỹ năng buff.</li></ul>',
            ],
            [
                'slug' => 'summoner', 'class_key' => 'summoner', 'icon' => 'wand-sparkles', 'order' => 6,
                'title' => 'Summoner', 'name' => 'Summoner',
                'excerpt' => 'Set đồ, wings và cách cộng điểm cho Summoner.',
                'intro' => 'Summoner là pháp sư nữ chuyên phép <strong>nguyền rủa (curse)</strong> gây sát thương theo thời gian và khống chế đối thủ. Sát thương phép cao, có kỹ năng debuff mạnh trong PvP.',
                'weapon' => 'Gậy/Sách (Stick/Book). Ưu tiên vũ khí Excellent tăng % sát thương phép/curse.',
                'tiers' => [
                    'Sơ cấp' => ['Mistery (34)'],
                    'Trung cấp' => ['Red Wing (56)', 'Ancient (75)'],
                    'Cao cấp' => ['Black Rose (91)', 'Lilium (113)'],
                    'Đỉnh cao' => ['Aura (122)'],
                ],
                'wings' => [['12_41', 'Wings of Curse', 'cấp 1'], ['12_42', 'Wings of Despair', 'cấp 2'], ['12_43', 'Wing of Dimension', 'cấp 3']],
                'build' => '<ul><li><strong>Energy</strong> — sát thương phép/curse, dồn nhiều nhất.</li><li><strong>Vitality</strong> — để sống trong giao tranh.</li><li><strong>Agility/Strength</strong> — đủ mặc đồ.</li></ul>',
            ],
            [
                'slug' => 'rage-fighter', 'class_key' => 'rage-fighter', 'icon' => 'hand-fist', 'order' => 7,
                'title' => 'Rage Fighter', 'name' => 'Rage Fighter',
                'excerpt' => 'Set đồ, wings và cách cộng điểm cho Rage Fighter.',
                'intro' => 'Rage Fighter là võ sĩ cận chiến dùng <strong>găng đấm</strong>, ra đòn nhanh, nhiều kỹ năng khống chế và combo. Máu và sát thương tốt, rất mạnh trong PvP tay đôi.',
                'weapon' => 'Găng đấm (Knuckle/Claw). Chọn loại theo hướng sát thương vật lý hoặc phép của Rage Fighter.',
                'tiers' => [
                    'Sơ cấp' => ['Leather (10)', 'Scale (28)'],
                    'Trung cấp' => ['Brass (38)', 'Plate (48)', 'Sacred (66)'],
                    'Cao cấp' => ['Storm Hard (82)', 'Piercing (101)'],
                    'Đỉnh cao' => ['Phoenix Soul (143)'],
                ],
                'wings' => [['12_49', 'Cape of Fighter', 'cấp 2 (Rage Fighter không có wing cấp 1)'], ['12_50', 'Cape of Overrule', 'cấp 3']],
                'build' => '<ul><li><strong>Strength</strong> — sát thương chính.</li><li><strong>Vitality</strong> — cao, võ sĩ cần trụ và combo.</li><li><strong>Agility</strong> — đủ mặc đồ và tốc đánh.</li></ul>',
            ],
        ];

        $out = [];
        foreach ($defs as $d) {
            $out[] = [
                'slug' => $d['slug'], 'category' => 'class', 'class_key' => $d['class_key'], 'icon' => $d['icon'],
                'title_vi' => $d['title'], 'title_en' => $d['title'],
                'excerpt_vi' => $d['excerpt'], 'excerpt_en' => null,
                'body_vi' => $this->classBody($d), 'body_en' => null,
                'sort_order' => $d['order'], 'is_published' => true,
            ];
        }

        return $out;
    }
}
