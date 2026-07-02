<?php

namespace Database\Seeders;

use App\Models\Guide;
use Illuminate\Database\Seeder;

/**
 * Seeds the pilot player guides (cẩm nang). Idempotent: upserts by slug so it
 * can run on every deploy without duplicating. Numbers in the wing-crafting and
 * gear sections are taken from the muss6 OpenMU Season 6 config (ChaosMixes.cs /
 * Armors.cs / Wings.cs); editorial advice (stat builds, farming) is flagged in a
 * .guide-note so a GM can verify/adjust in the admin editor.
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

    private function guides(): array
    {
        return [
            $this->wingsOverview(),
            $this->wingsCrafting(),
            $this->statBuilds(),
            $this->darkKnight(),
        ];
    }

    private function wingsOverview(): array
    {
        $body = <<<'HTML'
<p>Wings (cánh) là trang bị quan trọng bậc nhất ở muss6: tăng sát thương, tăng khả năng hấp thụ sát thương (giảm damage nhận vào) và cho phép bay/di chuyển. Mỗi class có dòng wing riêng, chia làm <strong>3 cấp</strong>.</p>

<h2>Wing theo class</h2>
<p>Bảng dưới là dòng wing của từng class theo đúng cấu hình máy chủ muss6:</p>
<div class="table-responsive">
<table>
<thead><tr><th>Class</th><th>Cấp 1</th><th>Cấp 2</th><th>Cấp 3</th></tr></thead>
<tbody>
<tr><td>Dark Knight (Blade Knight)</td><td>Wings of Satan</td><td>Wings of Dragon</td><td>Wing of Storm</td></tr>
<tr><td>Dark Wizard (Soul Master)</td><td>Wings of Heaven</td><td>Wings of Soul</td><td>Wing of Eternal</td></tr>
<tr><td>Fairy Elf (Muse Elf)</td><td>Wings of Elf</td><td>Wings of Spirits</td><td>Wing of Illusion</td></tr>
<tr><td>Magic Gladiator</td><td>Wings of Heaven / Satan</td><td>Wings of Darkness</td><td>Wing of Ruin</td></tr>
<tr><td>Dark Lord</td><td>— (dùng Cape)</td><td>Cape of Lord</td><td>Cape of Emperor</td></tr>
<tr><td>Summoner</td><td>Wings of Curse</td><td>Wings of Despair</td><td>Wing of Dimension</td></tr>
<tr><td>Rage Fighter</td><td>— (dùng Cape)</td><td>Cape of Fighter</td><td>Cape of Overrule</td></tr>
</tbody>
</table>
</div>

<div class="guide-note">
<strong>Lưu ý về cấu hình muss6:</strong> ở máy chủ này, Dark Wizard dùng <em>Wing of Eternal</em> còn Fairy Elf dùng <em>Wing of Illusion</em> ở cấp 3 (một số server khác đảo ngược hai wing này). Bảng trên lấy trực tiếp từ cấu hình game nên là chuẩn của muss6.
</div>

<h2>Sự khác biệt giữa các cấp</h2>
<ul>
<li><strong>Wing cấp 1</strong> — mở khoá sớm, tăng sát thương cơ bản. Cần đạt chuyển sinh (2nd class) với đa số class.</li>
<li><strong>Wing cấp 2</strong> — mạnh hơn hẳn, có thể kèm dòng Luck / Excellent khi chế tạo. Đây là mốc "đủ dùng" để đi Blood Castle, Devil Square, farm.</li>
<li><strong>Wing cấp 3</strong> — cao cấp nhất, phòng thủ và sát thương vượt trội, có 3 dòng option ngẫu nhiên. Là mục tiêu cày cuốc lâu dài.</li>
<li><strong>Dark Lord & Rage Fighter</strong> không có wing cấp 1; hai class này bắt đầu bằng <em>Cape</em> (tương đương wing cấp 2) rồi lên thẳng Cape cấp 3.</li>
</ul>

<p>Muốn biết cách chế từng cấp wing (nguyên liệu, tỉ lệ, nơi tìm vật phẩm), xem bài <strong>Cách xoay Wings tại Chaos Machine</strong>.</p>
HTML;

        return [
            'slug'        => 'tong-quan-wings',
            'category'    => 'wings',
            'class_key'   => null,
            'icon'        => 'dove',
            'title_vi'    => 'Tổng quan Wings (cấp 1 → 3)',
            'title_en'    => 'Wings overview (level 1 → 3)',
            'excerpt_vi'  => 'Dòng wing của từng class ở muss6 và khác biệt giữa 3 cấp.',
            'excerpt_en'  => 'Each class\'s wing line on muss6 and how the 3 levels differ.',
            'body_vi'     => $body,
            'body_en'     => null,
            'sort_order'  => 1,
            'is_published' => true,
        ];
    }

    private function wingsCrafting(): array
    {
        $body = <<<'HTML'
<p>Wings được chế tạo tại <strong>Chaos Goblin Machine</strong> (NPC ở thành Noria). Bỏ đủ nguyên liệu vào máy, trả phí zen rồi bấm "Combine". Nếu thất bại, vật phẩm chính có thể tụt cấp hoặc biến mất — nên đọc kỹ trước khi làm.</p>

<div class="guide-note">
Các con số dưới đây (nguyên liệu, số ngọc, tỉ lệ, zen) lấy trực tiếp từ cấu hình máy chủ muss6. Nếu GM chỉnh lại công thức trong admin, hãy cập nhật bài này.
</div>

<h2>Wing cấp 1</h2>
<ul>
<li><strong>Vật phẩm chính:</strong> 1 vũ khí Chaos (Chaos Dragon Axe / Chaos Nature Bow / Chaos Lightning Staff) +4 trở lên, có option (dòng +4/+8/+12...).</li>
<li><strong>Tuỳ chọn:</strong> thêm 1 vật phẩm bất kỳ +4 trở lên (có option) để tăng tỉ lệ.</li>
<li><strong>Ngọc:</strong> 1 Jewel of Chaos (bắt buộc) + thêm Jewel of Bless / Jewel of Soul để tăng % thành công.</li>
<li><strong>Phí:</strong> ~10.000 zen cho mỗi 1% tỉ lệ.</li>
<li><strong>Kết quả (ngẫu nhiên):</strong> Wings of Elf / Heaven / Satan / Curse.</li>
</ul>

<h2>Wing cấp 2</h2>
<ul>
<li><strong>Vật phẩm chính:</strong> 1 wing cấp 1 bất kỳ (+0 → +15).</li>
<li><strong>Tuỳ chọn:</strong> thêm 1 vật phẩm <em>excellent</em> +4 trở lên để tăng tỉ lệ.</li>
<li><strong>Ngọc:</strong> 1 Jewel of Chaos + 1 Loch's Feather (Lông vũ).</li>
<li><strong>Phí:</strong> 5.000.000 zen. <strong>Tỉ lệ tối đa 90%.</strong></li>
<li><strong>Kết quả (ngẫu nhiên):</strong> Wings of Spirits / Soul / Dragon / Darkness / Despair. Có 20% cơ hội ra kèm dòng Luck và 20% cơ hội kèm 1 dòng Excellent.</li>
</ul>

<h2>Wing cấp 3</h2>
<p>Wing cấp 3 làm qua <strong>2 bước</strong>.</p>
<h3>Bước 1 — Chế Feather of Condor</h3>
<ul>
<li>1 wing cấp 2 (hoặc Cape) +9 → +15, có option.</li>
<li>1 vật phẩm <strong>Ancient (đồ thần)</strong> +7 → +15, có ancient bonus + option.</li>
<li>1 Jewel of Chaos + 1 Jewel of Creation + 1 Packed Jewel of Soul.</li>
<li>Phí ~200.000 zen mỗi 1%. Tỉ lệ 1% → <strong>tối đa 60%</strong>.</li>
<li>Thành công nhận <strong>Feather of Condor</strong>.</li>
</ul>
<h3>Bước 2 — Chế Wing cấp 3</h3>
<ul>
<li>1 vật phẩm <em>excellent</em> +9 → +15.</li>
<li>1 Feather of Condor + 1 Flame of Condor.</li>
<li>1 Jewel of Chaos + 1 Jewel of Creation + 1 Packed Jewel of Soul + 1 Packed Jewel of Bless.</li>
<li>Tỉ lệ <strong>tối đa 40%</strong>.</li>
<li>Kết quả: wing cấp 3 tương ứng class (hoặc Cape of Emperor / Cape of Overrule).</li>
</ul>

<h2>Nguyên liệu & nơi tìm</h2>
<div class="table-responsive">
<table>
<thead><tr><th>Nguyên liệu</th><th>Dùng cho</th><th>Cách kiếm</th></tr></thead>
<tbody>
<tr><td>Jewel of Chaos</td><td>Cả 3 cấp</td><td>Thưởng Blood Castle, rơi từ quái cấp cao</td></tr>
<tr><td>Jewel of Bless / Soul</td><td>Cấp 1 (tăng %)</td><td>Rơi từ quái, thưởng Chaos Castle</td></tr>
<tr><td>Loch's Feather</td><td>Cấp 2</td><td>Rơi từ quái map cao (drop level 78+)</td></tr>
<tr><td>Jewel of Creation</td><td>Cấp 3</td><td>Rơi từ quái map cao / boss</td></tr>
<tr><td>Flame of Condor</td><td>Cấp 3 (bước 2)</td><td>Rơi từ boss / quái Kanturu</td></tr>
<tr><td>Packed Jewel</td><td>Cấp 3</td><td>Gộp 10 ngọc thường tại NPC đóng gói</td></tr>
</tbody>
</table>
</div>
<div class="guide-note">
Cột "Cách kiếm" là hướng dẫn chung theo Season 6. Nguồn rơi chính xác theo map/quái của muss6 sẽ được bổ sung từ cấu hình drop của máy chủ ở bản cập nhật sau.
</div>

<h2>Mẹo</h2>
<ul>
<li>Luôn cộng thêm ngọc / vật phẩm phụ để đẩy tỉ lệ lên cao nhất trước khi bấm ghép.</li>
<li>Giữ lại đồ excellent / ancient dư để làm nguyên liệu wing cấp 2 và 3.</li>
<li>Wing cấp 3 tỉ lệ thấp (≤40%) — chuẩn bị dư nguyên liệu, đừng nản khi thất bại.</li>
</ul>
HTML;

        return [
            'slug'        => 'cach-xoay-wings',
            'category'    => 'wings',
            'class_key'   => null,
            'icon'        => 'gears',
            'title_vi'    => 'Cách xoay Wings tại Chaos Machine',
            'title_en'    => 'How to craft wings at the Chaos Machine',
            'excerpt_vi'  => 'Công thức chế wing cấp 1, 2, 3: nguyên liệu, tỉ lệ, zen và nơi tìm vật phẩm.',
            'excerpt_en'  => 'Recipes for level 1/2/3 wings: materials, chances, zen and where to farm.',
            'body_vi'     => $body,
            'body_en'     => null,
            'sort_order'  => 2,
            'is_published' => true,
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
            'slug'        => 'cong-diem-cac-class',
            'category'    => 'stats',
            'class_key'   => null,
            'icon'        => 'chart-simple',
            'title_vi'    => 'Cách cộng điểm hiệu quả',
            'title_en'    => 'Effective stat builds',
            'excerpt_vi'  => 'Ý nghĩa 4 chỉ số và nguyên tắc cộng điểm chung cho mọi class.',
            'excerpt_en'  => 'What the core stats do and general point-allocation principles.',
            'body_vi'     => $body,
            'body_en'     => null,
            'sort_order'  => 1,
            'is_published' => true,
        ];
    }

    private function darkKnight(): array
    {
        $body = <<<'HTML'
<p>Dark Knight (chuyển sinh thành <strong>Blade Knight</strong>) là class cận chiến máu trâu, sát thương vật lý cao, dễ chơi — lựa chọn tốt cho người mới. Đánh gần, chịu đòn khoẻ, phù hợp cày cuốc lẫn PvP.</p>

<h2>Set đồ theo cấp độ</h2>
<p>Các bộ đồ Dark Knight có thể mặc, xếp theo cấp độ rơi (drop level) — lấy từ cấu hình muss6:</p>
<div class="table-responsive">
<table>
<thead><tr><th>Giai đoạn</th><th>Bộ đồ</th><th>Cấp độ rơi</th></tr></thead>
<tbody>
<tr><td>Sơ cấp</td><td>Leather → Bronze → Scale</td><td>10 – 28</td></tr>
<tr><td>Trung cấp</td><td>Brass → Plate → Dragon</td><td>38 – 59</td></tr>
<tr><td>Cao cấp</td><td>Ashcrow → Black Dragon → Dark Phoenix</td><td>75 – 100</td></tr>
<tr><td>Đỉnh cao</td><td>Great Dragon → Titan → Dragon Knight</td><td>126 – 140</td></tr>
</tbody>
</table>
</div>

<h2>Đồ thường, Excellent và Đồ thần</h2>
<p>Cùng một bộ đồ có thể xuất hiện ở 3 "hạng" khác nhau — đây là điều quyết định sức mạnh thật sự, không chỉ là tên bộ:</p>
<ul>
<li><strong>Đồ thường</strong> — chỉ có phòng thủ cơ bản. Dùng để qua giai đoạn đầu.</li>
<li><strong>Đồ Excellent (đồ hoàng kim)</strong> — có các dòng option excellent (hồi HP/MP khi đánh, tăng % sát thương, giảm damage nhận...). Rơi từ Blood Castle, hộp Kundun, boss.</li>
<li><strong>Đồ thần (Ancient)</strong> — thuộc một <em>bộ Ancient</em>, có thêm chỉ số cổ (ancient option) và <strong>set bonus</strong> khi mặc nhiều món cùng bộ. Đây là "đồ thần" mà người chơi hay nhắc tới. Kiếm từ Kanturu, Land of Trials, hoặc chế qua Chaos Machine.</li>
</ul>
<div class="guide-note">
"Đồ thần" là khái niệm về <strong>hạng option (Ancient)</strong> của món đồ, không phải một bộ đồ riêng. Vì vậy một bộ như Dragon Knight có thể tồn tại ở cả bản thường, excellent và ancient. Danh sách bộ Ancient chính xác của muss6 sẽ được bổ sung từ cấu hình ở bản sau.
</div>

<h2>Wings cho Dark Knight</h2>
<p>Lộ trình wing của Dark Knight:</p>
<ol>
<li><strong>Wings of Satan</strong> (cấp 1)</li>
<li><strong>Wings of Dragon</strong> (cấp 2) — mốc quan trọng để farm hiệu quả</li>
<li><strong>Wing of Storm</strong> (cấp 3) — mục tiêu cuối</li>
</ol>
<p>Cách chế chi tiết xem bài <strong>Cách xoay Wings tại Chaos Machine</strong>.</p>

<h2>Cộng điểm cho Dark Knight</h2>
<ul>
<li><strong>Strength</strong> — chỉ số sát thương chính, dồn nhiều nhất.</li>
<li><strong>Agility</strong> — cộng đủ để mặc bộ đồ mục tiêu và đạt tốc đánh, không cộng thừa.</li>
<li><strong>Vitality</strong> — tăng dần khi lên đồ tốt, đặc biệt nếu thiên về PvP.</li>
<li><strong>Energy</strong> — hầu như không cần với Dark Knight.</li>
</ul>
<p><strong>Gợi ý theo mục tiêu:</strong></p>
<ul>
<li><em>Cày PvE / farm:</em> tối đa Strength, Agility vừa đủ mặc đồ, ít Vitality.</li>
<li><em>PvP:</em> Strength cao + Vitality khá để đủ máu; Agility đủ đồ và né.</li>
</ul>
<div class="guide-note">
Build trên theo kinh nghiệm Season 6. GM nên rà lại theo tỉ lệ và cấu hình cụ thể của muss6.
</div>
HTML;

        return [
            'slug'        => 'dark-knight',
            'category'    => 'class',
            'class_key'   => 'dark-knight',
            'icon'        => 'khanda',
            'title_vi'    => 'Dark Knight (Blade Knight)',
            'title_en'    => 'Dark Knight (Blade Knight)',
            'excerpt_vi'  => 'Set đồ, wings và cách cộng điểm cho Dark Knight.',
            'excerpt_en'  => 'Gear sets, wings and stat build for the Dark Knight.',
            'body_vi'     => $body,
            'body_en'     => null,
            'sort_order'  => 1,
            'is_published' => true,
        ];
    }
}
