<?php

namespace Database\Seeders;

use App\Models\Guide;
use Illuminate\Database\Seeder;

/**
 * Seeds the player guides (cẩm nang), bilingual (vi + en). Idempotent: upserts by slug.
 *
 * Numbers come from the muss6 OpenMU Season 6 config DB (ItemDefinition, AttributeRequirement,
 * ItemBasePowerUpDefinition, ItemDefinitionCharacterClass), ChaosMixes.cs for wing recipes,
 * and GameMapDefinition/MonsterSpawnArea for farm maps. Both language bodies are generated from
 * the same data + a small label pack so English never falls back to Vietnamese.
 * Item art: public/images/items/item_{group}_{number}_0.png (armor groups 7-11; weapons 0-5).
 */
class GuideSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->guides() as $g) {
            Guide::updateOrCreate(['slug' => $g['slug']], array_merge($g, ['author_account_id' => null]));
        }
    }

    private function guides(): array
    {
        return array_merge(
            [
                $this->gettingStartedGuide(),
                $this->characterManagementGuide(),
                $this->offstoreGuide(),
                $this->offlevelGuide(),
                $this->eventsGuide(),
                $this->wingsOverview(),
                $this->wingsCrafting(),
                $this->setDetails(),
                $this->weaponsPage(),
                $this->statBuilds(),
            ],
            $this->classGuides(),
        );
    }

    // ---------------------------------------------------------------- Labels --

    /** Micro-string label pack per locale (used by the shared render helpers). */
    private function t(string $loc): array
    {
        $p = [
            'vi' => [
                'filter' => 'Lọc theo class (chọn nhiều):', 'farm' => 'Farm ở:',
                'drop' => 'Cấp rơi', 'def' => 'Phòng thủ', 'str' => 'Sức mạnh', 'agi' => 'Nhanh nhẹn', 'lvl' => 'Cấp NV',
                'dmg' => 'Sát thương', 'magic' => 'Sức mạnh phép', 'skill' => 'Vật phẩm kỹ năng (sách phép)', 'to' => 'tới',
                'mat' => 'Nguyên liệu', 'qty' => 'Số lượng / ghi chú',
                'setcols' => ['Bộ (5 món)', 'Tên & class', 'Chỉ số', 'Yêu cầu', 'Farm ở'],
                'wpncols' => ['Ảnh', 'Tên & loại', 'Sát thương', 'Yêu cầu', 'Farm ở'],
                'wtypes' => [0 => 'Kiếm/Găng', 1 => 'Rìu', 2 => 'Chuỳ/Gậy quyền', 3 => 'Giáo', 4 => 'Cung/Nỏ', 5 => 'Gậy/Sách'],
            ],
            'en' => [
                'filter' => 'Filter by class (multi-select):', 'farm' => 'Farm at:',
                'drop' => 'Drop lv', 'def' => 'Defense', 'str' => 'Strength', 'agi' => 'Agility', 'lvl' => 'Char lv',
                'dmg' => 'Damage', 'magic' => 'Magic power', 'skill' => 'Skill item (spell book)', 'to' => 'to',
                'mat' => 'Material', 'qty' => 'Amount / note',
                'setcols' => ['Set (5 pieces)', 'Name & class', 'Stats', 'Requirements', 'Farm at'],
                'wpncols' => ['Art', 'Name & type', 'Damage', 'Requirements', 'Farm at'],
                'wtypes' => [0 => 'Sword/Glove', 1 => 'Axe', 2 => 'Mace/Scepter', 3 => 'Spear', 4 => 'Bow/Crossbow', 5 => 'Staff/Stick'],
            ],
        ];

        return $p[$loc];
    }

    // ---------------------------------------------------------------- Helpers --

    private function img(string $code, string $alt): string
    {
        return '<img src="/images/items/item_' . $code . '_0.png" alt="' . $alt . '" title="' . $alt . '" onerror="this.style.display=\'none\'">';
    }

    private function wingCell(string $code, string $name): string
    {
        return '<div class="mu-wing-cell">' . $this->img($code, $name) . '<span>' . $name . '</span></div>';
    }

    private function filterBar(string $targetId, string $loc): string
    {
        $classes = ['Dark Knight', 'Dark Wizard', 'Fairy Elf', 'Magic Gladiator', 'Dark Lord', 'Summoner', 'Rage Fighter'];
        $chips = '';
        foreach ($classes as $c) {
            $chips .= '<button type="button" class="mu-chip" data-class="' . $c . '">' . $c . '</button>';
        }

        return '<div class="mu-filter" data-target="#' . $targetId . '"><span class="mu-filter-label">'
            . $this->t($loc)['filter'] . '</span>' . $chips . '</div>';
    }

    /** Materials table. $rows = [imgCode|null, name, note]. */
    private function matTable(array $rows, string $loc): string
    {
        $t = $this->t($loc);
        $body = '';
        foreach ($rows as $r) {
            $icon = $r[0] ? $this->img($r[0], $r[1]) : '';
            $body .= '<tr><td class="mu-ic">' . $icon . '</td><td>' . $r[1] . '</td><td>' . $r[2] . '</td></tr>';
        }

        return '<div class="table-responsive"><table><thead><tr><th></th><th>' . $t['mat'] . '</th><th>'
            . $t['qty'] . '</th></tr></thead><tbody>' . $body . '</tbody></table></div>';
    }

    /** Material card. $sources = list of html strings. */
    private function matCard(string $code, string $name, string $use, array $sources): string
    {
        $li = '';
        foreach ($sources as $s) {
            $li .= '<li>' . $s . '</li>';
        }

        return '<div class="mu-mat">' . $this->img($code, $name)
            . '<div class="mu-mat-info"><div class="mu-mat-name">' . $name . '</div>'
            . '<div class="mu-mat-use">' . $use . '</div><ul>' . $li . '</ul></div></div>';
    }

    private function setRow(array $s, string $loc): string
    {
        [$num, $name, $drop, $def, $str, $agi, $lvl, $classes] = $s;
        $t = $this->t($loc);

        $pieces = '';
        foreach ([7, 8, 9, 10, 11] as $g) {
            $pieces .= $this->img($g . '_' . $num, $name);
        }

        $req = $t['str'] . ' ' . $str;
        if ($agi > 0) {
            $req .= ', ' . $t['agi'] . ' ' . $agi;
        }
        if ($lvl > 0) {
            $req .= ', ' . $t['lvl'] . ' ' . $lvl;
        }

        return '<tr data-classes="' . str_replace(', ', '|', $classes) . '">'
            . '<td><div class="mu-set-pieces">' . $pieces . '</div></td>'
            . '<td><div class="it-name">' . $name . '</div><div class="it-sub">' . $classes . '</div></td>'
            . '<td>' . $t['drop'] . ' ' . $drop . '<br>' . $t['def'] . ' ' . $def . '</td>'
            . '<td>' . $req . '</td>'
            . '<td>' . $this->farmMapFor($drop, $loc) . '</td></tr>';
    }

    private function weaponRow(int $group, array $w, string $loc): string
    {
        [$num, $name, $drop, $mind, $maxd, $rise, $str, $agi, $lvl, $classes] = $w;
        $t = $this->t($loc);
        $typeLabel = $t['wtypes'][$group];

        if ($mind === 0 && $maxd === 0 && $rise === 0) {
            $dmg = $t['skill'];
        } else {
            $parts = [];
            if ($maxd > 0) {
                $parts[] = $t['dmg'] . ' ' . $mind . ' ' . $t['to'] . ' ' . $maxd;
            }
            if ($rise > 0) {
                $parts[] = $t['magic'] . ' +' . $rise . '%';
            }
            $dmg = implode('<br>', $parts);
        }

        $req = $t['drop'] . ' ' . $drop;
        if ($str > 0) {
            $req .= ', ' . $t['str'] . ' ' . $str;
        }
        if ($agi > 0) {
            $req .= ', ' . $t['agi'] . ' ' . $agi;
        }
        if ($lvl > 0) {
            $req .= ', ' . $t['lvl'] . ' ' . $lvl;
        }

        return '<tr data-classes="' . str_replace(', ', '|', $classes) . '">'
            . '<td class="mu-ic">' . $this->img($group . '_' . $num, $name) . '</td>'
            . '<td><div class="it-name">' . $name . '</div><div class="it-sub">' . $typeLabel . ' · ' . $classes . '</div></td>'
            . '<td>' . $dmg . '</td><td>' . $req . '</td>'
            . '<td>' . $this->farmMapFor($drop, $loc) . '</td></tr>';
    }

    /** Suggested hunting maps by drop level (from the muss6 map monster-level ranges). */
    private function farmMapFor(int $drop, string $loc): string
    {
        $b = match (true) {
            $drop <= 30  => ['Lorencia, Noria, Devias, Elveland', 'quái cấp thấp', 'low-level monsters'],
            $drop <= 50  => ['Dungeon, Devias, Atlans', 'quái cấp 20 tới 74', 'monsters lv 20 to 74'],
            $drop <= 66  => ['Lost Tower, Atlans, Dungeon', 'quái cấp 43 tới 90', 'monsters lv 43 to 90'],
            $drop <= 80  => ['Tarkan, Aida, Icarus, Dungeon', 'quái cấp 72 tới 108', 'monsters lv 72 to 108'],
            $drop <= 100 => ['Aida, Icarus, Land of Trials, Kanturu, Vulcanus', 'quái cấp 75 tới 129', 'monsters lv 75 to 129'],
            $drop <= 120 => ['Vulcanus, Kanturu, Karutan, Aida (Bloody), Swamp of Calmness', 'quái cấp 90 tới 137', 'monsters lv 90 to 137'],
            $drop <= 135 => ['LaCleon (Raklion), Swamp of Calmness, Kanturu', 'quái cấp 100 tới 148', 'monsters lv 100 to 148'],
            default      => ['LaCleon (Raklion)', 'quái cấp 140+ như Dark Mammoth, Dark Giant, Dark Iron Knight', 'lv 140+ like Dark Mammoth, Dark Giant, Dark Iron Knight'],
        };

        return $b[0] . ' (' . ($loc === 'vi' ? $b[1] : $b[2]) . ')';
    }

    /** Pick localized text from a ['vi'=>, 'en'=>] pair. */
    private function pick(array $pair, string $loc): string
    {
        return $pair[$loc];
    }

    // ---------------------------------------------------------------- Wings --

    private function wingsOverview(): array
    {
        $build = function (string $loc) {
            $w = fn (string $code, string $name) => $this->wingCell($code, $name);
            $h = $loc === 'vi'
                ? ['Class', 'Cấp 1', 'Cấp 2', 'Cấp 3']
                : ['Class', 'Level 1', 'Level 2', 'Level 3'];
            $intro = $this->pick([
                'vi' => 'Wings (cánh) là trang bị quan trọng bậc nhất ở muss6: tăng sát thương, tăng khả năng hấp thụ sát thương (giảm damage nhận vào) và cho phép bay. Mỗi class có dòng wing riêng, chia làm 3 cấp.',
                'en' => 'Wings are one of the most important items on muss6: they boost damage, absorb incoming damage, and let you fly. Each class has its own wing line, split into 3 levels.',
            ], $loc);
            $head = $this->pick(['vi' => 'Wing theo class', 'en' => 'Wings by class'], $loc);
            $sub = $this->pick([
                'vi' => 'Bảng dưới là dòng wing của từng class theo đúng cấu hình máy chủ muss6:',
                'en' => 'Each class\'s wing line, straight from the muss6 config:',
            ], $loc);
            $note = $this->pick([
                'vi' => '<strong>Lưu ý cấu hình muss6:</strong> ở máy chủ này, Dark Wizard dùng <em>Wing of Eternal</em> còn Fairy Elf dùng <em>Wing of Illusion</em> ở cấp 3 (một số server khác đảo ngược hai wing này). Bảng lấy trực tiếp từ cấu hình game.',
                'en' => '<strong>muss6 config note:</strong> here Dark Wizard uses <em>Wing of Eternal</em> and Fairy Elf uses <em>Wing of Illusion</em> at level 3 (some servers swap these two). The table is taken straight from the game config.',
            ], $loc);
            $diffHead = $this->pick(['vi' => 'Khác biệt giữa các cấp', 'en' => 'How the levels differ'], $loc);
            $diff = $this->pick([
                'vi' => '<li><strong>Wing cấp 1</strong>: mở khoá sớm, tăng sát thương cơ bản.</li>'
                    . '<li><strong>Wing cấp 2</strong>: mạnh hơn hẳn, có thể kèm dòng Luck / Excellent khi chế. Mốc "đủ dùng" để đi Blood Castle, Devil Square, farm.</li>'
                    . '<li><strong>Wing cấp 3</strong>: cao cấp nhất, phòng thủ và sát thương vượt trội, có 3 dòng option ngẫu nhiên.</li>'
                    . '<li><strong>Dark Lord & Rage Fighter</strong> không có wing cấp 1; bắt đầu bằng <em>Cape</em> (tương đương cấp 2) rồi lên Cape cấp 3.</li>',
                'en' => '<li><strong>Level 1 wing</strong>: unlocks early, adds base damage.</li>'
                    . '<li><strong>Level 2 wing</strong>: much stronger, can roll Luck / Excellent when crafted. The "good enough" mark for Blood Castle, Devil Square and farming.</li>'
                    . '<li><strong>Level 3 wing</strong>: top tier, big defense and damage, with 3 random option lines.</li>'
                    . '<li><strong>Dark Lord & Rage Fighter</strong> have no level-1 wing; they start with a <em>Cape</em> (level-2 equivalent) then go to the level-3 Cape.</li>',
            ], $loc);
            $craftLink = $this->pick([
                'vi' => 'Cách chế từng cấp (nguyên liệu, tỉ lệ, nơi tìm) xem bài <strong>Cách xoay Wings tại Chaos Machine</strong>.',
                'en' => 'For how to craft each level (materials, chances, where to farm) see <strong>How to craft wings at the Chaos Machine</strong>.',
            ], $loc);
            $capeNoLv1 = $loc === 'vi' ? 'Không có' : 'None';

            return "<p>{$intro}</p>\n<h2>{$head}</h2>\n<p>{$sub}</p>\n"
                . '<div class="table-responsive"><table>'
                . "<thead><tr><th>{$h[0]}</th><th>{$h[1]}</th><th>{$h[2]}</th><th>{$h[3]}</th></tr></thead><tbody>"
                . "<tr><td>Dark Knight</td><td>{$w('12_2', 'Satan')}</td><td>{$w('12_5', 'Dragon')}</td><td>{$w('12_36', 'Storm')}</td></tr>"
                . "<tr><td>Dark Wizard</td><td>{$w('12_1', 'Heaven')}</td><td>{$w('12_4', 'Soul')}</td><td>{$w('12_37', 'Eternal')}</td></tr>"
                . "<tr><td>Fairy Elf</td><td>{$w('12_0', 'Elf')}</td><td>{$w('12_3', 'Spirits')}</td><td>{$w('12_38', 'Illusion')}</td></tr>"
                . "<tr><td>Magic Gladiator</td><td>{$w('12_2', 'Heaven/Satan')}</td><td>{$w('12_6', 'Darkness')}</td><td>{$w('12_39', 'Ruin')}</td></tr>"
                . "<tr><td>Dark Lord</td><td>{$capeNoLv1}</td><td>{$w('13_30', 'Cape of Lord')}</td><td>{$w('12_40', 'Cape of Emperor')}</td></tr>"
                . "<tr><td>Summoner</td><td>{$w('12_41', 'Curse')}</td><td>{$w('12_42', 'Despair')}</td><td>{$w('12_43', 'Dimension')}</td></tr>"
                . "<tr><td>Rage Fighter</td><td>{$capeNoLv1}</td><td>{$w('12_49', 'Cape of Fighter')}</td><td>{$w('12_50', 'Cape of Overrule')}</td></tr>"
                . '</tbody></table></div>'
                . "<div class=\"guide-note\">{$note}</div>"
                . "<h2>{$diffHead}</h2><ul>{$diff}</ul><p>{$craftLink}</p>";
        };

        return [
            'slug' => 'tong-quan-wings', 'category' => 'wings', 'class_key' => null, 'icon' => 'dove',
            'title_vi' => 'Tổng quan Wings (cấp 1 → 3)', 'title_en' => 'Wings overview (level 1 → 3)',
            'excerpt_vi' => 'Dòng wing của từng class ở muss6 và khác biệt giữa 3 cấp.',
            'excerpt_en' => 'Each class\'s wing line on muss6 and how the 3 levels differ.',
            'body_vi' => $build('vi'), 'body_en' => $build('en'), 'sort_order' => 1, 'is_published' => true,
        ];
    }

    private function wingsCrafting(): array
    {
        $build = function (string $loc) {
            $vi = $loc === 'vi';

            $tbl1 = $this->matTable([
                ['2_6', $vi ? 'Vũ khí Chaos +4 trở lên' : 'A Chaos weapon +4 or higher', $vi ? 'Chaos Dragon Axe / Chaos Nature Bow / Chaos Lightning Staff (bắt buộc)' : 'Chaos Dragon Axe / Chaos Nature Bow / Chaos Lightning Staff (required)'],
                [null, $vi ? 'Vật phẩm phụ +4 trở lên' : 'Extra item +4 or higher', $vi ? 'tuỳ chọn, tăng %' : 'optional, raises %'],
                ['12_15', 'Jewel of Chaos', $vi ? '1, bắt buộc' : '1, required'],
                ['14_13', 'Jewel of Bless', $vi ? 'tuỳ chọn, tăng %' : 'optional, raises %'],
                ['14_14', 'Jewel of Soul', $vi ? 'tuỳ chọn, tăng %' : 'optional, raises %'],
            ], $loc);

            $tbl2 = $this->matTable([
                [null, $vi ? 'Wing cấp 1 bất kỳ (+0 trở lên)' : 'Any level-1 wing (+0 or higher)', '1'],
                [null, $vi ? 'Đồ Excellent +4 trở lên' : 'Excellent item +4 or higher', $vi ? 'tuỳ chọn, tăng %' : 'optional, raises %'],
                ['12_15', 'Jewel of Chaos', '1'],
                ['13_14', "Loch's Feather", '1'],
            ], $loc);

            $tbl3a = $this->matTable([
                [null, $vi ? 'Wing cấp 2 hoặc Cape (+9 trở lên)' : 'Any level-2 wing or Cape (+9 or higher)', '1'],
                [null, $vi ? 'Đồ Ancient (đồ thần) +7 trở lên' : 'An Ancient item (đồ thần) +7 or higher', '1'],
                ['12_15', 'Jewel of Chaos', '1'],
                ['14_22', 'Jewel of Creation', '1'],
                ['12_31', 'Packed Jewel of Soul', '1'],
            ], $loc);

            $tbl3b = $this->matTable([
                [null, $vi ? 'Đồ Excellent +9 trở lên' : 'Excellent item +9 or higher', '1'],
                ['13_53', 'Feather of Condor', '1'],
                ['13_52', 'Flame of Condor', '1'],
                ['12_15', 'Jewel of Chaos', '1'],
                ['14_22', 'Jewel of Creation', '1'],
                ['12_31', 'Packed Jewel of Soul', '1'],
                ['12_30', 'Packed Jewel of Bless', '1'],
            ], $loc);

            $matGrid = '<div class="mu-mat-grid">'
                . $this->matCard('12_15', 'Jewel of Chaos', $vi ? 'Dùng: cả 3 cấp wing' : 'Used in: all 3 wing levels', [
                    $vi ? 'Rơi từ <strong>mọi quái, mọi map</strong> (~0.1%)' : 'Drops from <strong>any monster, any map</strong> (~0.1%)',
                    $vi ? 'Thưởng <strong>Chaos Castle</strong>: 90% cho người thắng' : '<strong>Chaos Castle</strong> reward: 90% for the winner',
                    $vi ? 'Sự kiện <strong>Red Dragon</strong>: rơi 100%' : '<strong>Red Dragon</strong> invasion: 100% drop',
                ])
                . $this->matCard('14_13', 'Jewel of Bless', $vi ? 'Dùng: wing cấp 1 (tăng %)' : 'Used in: level-1 wing (raises %)', [
                    $vi ? 'Rơi từ mọi quái (~0.1%)' : 'Drops from any monster (~0.1%)',
                    $vi ? 'Chaos Castle; sự kiện Red Dragon' : 'Chaos Castle; Red Dragon invasion',
                ])
                . $this->matCard('14_14', 'Jewel of Soul', $vi ? 'Dùng: wing cấp 1 (tăng %)' : 'Used in: level-1 wing (raises %)', [
                    $vi ? 'Rơi từ mọi quái (~0.1%)' : 'Drops from any monster (~0.1%)',
                    $vi ? 'Chaos Castle; sự kiện Red Dragon' : 'Chaos Castle; Red Dragon invasion',
                ])
                . $this->matCard('14_22', 'Jewel of Creation', $vi ? 'Dùng: wing cấp 3' : 'Used in: level-3 wing', [
                    $vi ? 'Rơi từ quái <strong>level 72+</strong> (~0.1%)' : 'Drops from <strong>lv 72+</strong> monsters (~0.1%)',
                    'Chaos Castle',
                ])
                . $this->matCard('14_16', 'Jewel of Life', $vi ? 'Dùng: nâng cấp option đồ' : 'Used in: item option upgrades', [
                    $vi ? 'Rơi từ quái <strong>level 72+</strong> (~0.1%)' : 'Drops from <strong>lv 72+</strong> monsters (~0.1%)',
                    $vi ? 'Thưởng <strong>Chaos Castle</strong> cho người thắng' : '<strong>Chaos Castle</strong> reward for the winner',
                ])
                . $this->matCard('13_14', "Loch's Feather", $vi ? 'Dùng: wing cấp 2' : 'Used in: level-2 wing', [
                    $vi ? '<strong>Chỉ có ở map Icarus</strong>, quái level 82+ (~0.1%)' : '<strong>Icarus map only</strong>, lv 82+ monsters (~0.1%)',
                    ($vi ? 'Quái rơi: ' : 'Dropped by: ') . 'Queen Rainer (82), Drakan (86), Alpha Crust (92), Phantom Knight (96), Great Drakan (100), Dark Phoenix (108)',
                ])
                . $this->matCard('13_52', 'Flame of Condor', $vi ? 'Dùng: wing cấp 3 (bước 2)' : 'Used in: level-3 wing (step 2)', [
                    $vi ? '<strong>Chỉ có ở map Barracks of Balgass</strong> (~0.1%)' : '<strong>Barracks of Balgass only</strong> (~0.1%)',
                    ($vi ? 'Quái rơi: ' : 'Dropped by: ') . 'Balram (117), Death Spirit (119), Soram (119)',
                ])
                . $this->matCard('13_53', 'Feather of Condor', $vi ? 'Dùng: wing cấp 3' : 'Used in: level-3 wing', [
                    $vi ? 'Không rơi từ quái, chỉ <strong>chế được</strong> ở bước 1' : 'Not dropped, only <strong>craftable</strong> in step 1',
                ])
                . '</div>';

            $fh = $vi ? ['Map', 'Level quái', 'Quái tiêu biểu'] : ['Map', 'Monster level', 'Notable monsters'];
            $farmIntro = $vi
                ? 'Ngọc rơi theo cơ chế <strong>toàn cục</strong> (mọi quái ~0.1%), nên chọn map hợp level nhân vật, quái đông và giết nhanh:'
                : 'Jewels drop <strong>globally</strong> (any monster ~0.1%), so pick a map that matches your level with dense, fast-to-kill monsters:';
            $farmTable = "<h3>" . ($vi ? 'Farm ngọc ở map nào?' : 'Where to farm jewels?') . "</h3><p>{$farmIntro}</p>"
                . '<div class="table-responsive"><table>'
                . "<thead><tr><th>{$fh[0]}</th><th>{$fh[1]}</th><th>{$fh[2]}</th></tr></thead><tbody>"
                . '<tr><td>Lorencia / Noria / Devias / Elveland</td><td>2-48</td><td>' . ($vi ? 'Khởi đầu, quái yếu' : 'Starter, weak monsters') . '</td></tr>'
                . '<tr><td>Dungeon</td><td>19-80</td><td>Poison Bull, Gorgon, Dark Knight</td></tr>'
                . '<tr><td>Lost Tower</td><td>47-90</td><td>Death Knight, Devil, Balrog</td></tr>'
                . '<tr><td>Atlans</td><td>43-74</td><td>Lizard King, Hydra, Great Bahamut</td></tr>'
                . '<tr><td>Tarkan</td><td>72-93</td><td>Iron Wheel, Beam Knight, Death Beam Knight</td></tr>'
                . '<tr><td>Icarus</td><td>75-108</td><td>Great Drakan, Phantom Knight, Dark Phoenix</td></tr>'
                . '<tr><td>Aida</td><td>72-120</td><td>Witch Queen, Hell Maine, Bloody Witch Queen</td></tr>'
                . '<tr><td>Land of Trials</td><td>75-128</td><td>Fire Golem, Queen Bee, Erohim</td></tr>'
                . '<tr><td>Kanturu</td><td>80-129</td><td>Berserker, Gigantis, Genocider Warrior</td></tr>'
                . '<tr><td>Karutan</td><td>99-120</td><td>Orcus, Crypta, Narcondra</td></tr>'
                . '<tr><td>Vulcanus</td><td>90-124</td><td>Blood Assassin, Burning Lava Giant, Zombie Fighter</td></tr>'
                . '<tr><td>Swamp of Calmness</td><td>95-137</td><td>Shadow Knight, Sapi Queen, Shadow Master</td></tr>'
                . '<tr><td>LaCleon (Raklion)</td><td>102-148</td><td>Ice Giant, Iron Knight, Dark Iron Knight</td></tr>'
                . '</tbody></table></div>';

            // section prose
            $x = $vi ? [
                'lead' => 'Wings được chế tại <strong>Chaos Goblin Machine</strong> (NPC ở thành Noria). Bỏ đủ nguyên liệu, trả phí zen rồi bấm "Combine". Thất bại có thể làm vật phẩm chính tụt cấp hoặc biến mất.',
                'note' => 'Các con số dưới đây (nguyên liệu, số ngọc, tỉ lệ, zen) và nguồn rơi lấy trực tiếp từ cấu hình muss6.',
                'l1' => 'Wing cấp 1', 'l1p' => 'Phí ~10.000 zen mỗi 1% tỉ lệ. Kết quả (ngẫu nhiên): Wings of Elf / Heaven / Satan / Curse.',
                'l2' => 'Wing cấp 2', 'l2p' => 'Phí 5.000.000 zen, tỉ lệ tối đa 90%. Kết quả (ngẫu nhiên): Wings of Spirits / Soul / Dragon / Darkness / Despair (20% kèm Luck, 20% kèm 1 dòng Excellent).',
                'l3' => 'Wing cấp 3', 'l3p' => 'Làm qua 2 bước.',
                's1' => 'Bước 1: Chế Feather of Condor', 's1p' => 'Phí ~200.000 zen mỗi 1%, tỉ lệ 1% tới tối đa 60%. Thành công nhận Feather of Condor.',
                's2' => 'Bước 2: Chế Wing cấp 3', 's2p' => 'Tỉ lệ tối đa 40%. Kết quả: wing cấp 3 của class (hoặc Cape of Emperor / Overrule).',
                'math' => 'Nguyên liệu & nơi tìm',
                'tiph' => 'Mẹo', 'tips' => '<li>Cộng thêm ngọc / vật phẩm phụ để đẩy tỉ lệ lên cao nhất trước khi ghép.</li><li>Packed Jewel = gộp 10 ngọc thường tại NPC đóng gói.</li><li>Wing cấp 3 tỉ lệ thấp (dưới 40%), chuẩn bị dư nguyên liệu.</li>',
            ] : [
                'lead' => 'Wings are crafted at the <strong>Chaos Goblin Machine</strong> (an NPC in Noria). Put in all the materials, pay the zen and press "Combine". On failure the main item can drop a level or be lost.',
                'note' => 'The numbers below (materials, jewels, chances, zen) and drop sources are taken straight from the muss6 config.',
                'l1' => 'Level 1 wing', 'l1p' => 'Cost ~10,000 zen per 1% chance. Result (random): Wings of Elf / Heaven / Satan / Curse.',
                'l2' => 'Level 2 wing', 'l2p' => 'Cost 5,000,000 zen, max 90% chance. Result (random): Wings of Spirits / Soul / Dragon / Darkness / Despair (20% Luck, 20% one Excellent option).',
                'l3' => 'Level 3 wing', 'l3p' => 'Done in 2 steps.',
                's1' => 'Step 1: craft Feather of Condor', 's1p' => 'Cost ~200,000 zen per 1%, chance 1% up to 60%. Success gives a Feather of Condor.',
                's2' => 'Step 2: craft the level-3 wing', 's2p' => 'Max 40% chance. Result: your class\'s level-3 wing (or Cape of Emperor / Overrule).',
                'math' => 'Materials & where to find them',
                'tiph' => 'Tips', 'tips' => '<li>Add extra jewels / items to push the chance as high as possible before combining.</li><li>Packed Jewel = 10 normal jewels bundled at the packing NPC.</li><li>Level-3 wings have a low chance (under 40%), keep spare materials.</li>',
            ];

            $fig = static fn ($file, $cap) => '<figure class="guide-fig"><img src="/images/guides/' . $file . '" loading="lazy" alt="' . $cap . '"><figcaption>' . $cap . '</figcaption></figure>';
            $hUse = $vi ? 'Mở khung chế ở Noria' : 'Open the Chaos Machine in Noria';
            $useIntro = $vi
                ? 'Tới thành <strong>Noria</strong>, tìm <strong>Chaos Goblin</strong> (cỗ máy phát sáng xanh, quanh có mấy con goblin tím) rồi bấm vào để mở khung chế.'
                : 'Go to <strong>Noria</strong>, find the <strong>Chaos Goblin</strong> (the blue-glowing machine surrounded by purple goblins) and click it to open the combination window.';
            $useMachine = "<h2>{$hUse}</h2><p>{$useIntro}</p>"
                . $fig('wings-1-machine.jpg', $vi ? 'Chaos Goblin Machine ở Noria (gần toạ độ 180,103).' : 'The Chaos Goblin Machine in Noria (near 180,103).')
                . $fig('wings-2-menu.jpg', $vi ? 'Menu chọn kiểu ghép: "Regular Combination" dùng để chế Wings (7 loại), ngoài ra còn ghép vũ khí Chaos và cộng option +380.' : 'The method menu: "Regular Combination" is used for Wings (7 types), plus Chaos weapons and +380 item options.')
                . $fig('wings-3-grid.jpg', $vi ? 'Lưới ghép: bỏ đủ nguyên liệu vào đây, xem tỉ lệ thành công và phí zen rồi bấm ghép.' : 'The combination grid: put the materials here, check the success rate and zen cost, then combine.');

            return "<p>{$x['lead']}</p><div class=\"guide-note\">{$x['note']}</div>"
                . $useMachine
                . "<h2>{$x['l1']}</h2>{$tbl1}<p>{$x['l1p']}</p>"
                . "<h2>{$x['l2']}</h2>{$tbl2}<p>{$x['l2p']}</p>"
                . "<h2>{$x['l3']}</h2><p>{$x['l3p']}</p>"
                . "<h3>{$x['s1']}</h3><p>{$x['s1p']}</p>{$tbl3a}"
                . "<h3>{$x['s2']}</h3><p>{$x['s2p']}</p>{$tbl3b}"
                . "<h2>{$x['math']}</h2>{$matGrid}{$farmTable}"
                . "<h2>{$x['tiph']}</h2><ul>{$x['tips']}</ul>";
        };

        return [
            'slug' => 'cach-xoay-wings', 'category' => 'wings', 'class_key' => null, 'icon' => 'gears',
            'title_vi' => 'Cách xoay Wings tại Chaos Machine', 'title_en' => 'How to craft wings at the Chaos Machine',
            'excerpt_vi' => 'Công thức chế wing cấp 1, 2, 3: nguyên liệu, tỉ lệ, zen và nơi tìm vật phẩm.',
            'excerpt_en' => 'Recipes for level 1/2/3 wings: materials, chances, zen and where to farm.',
            'body_vi' => $build('vi'), 'body_en' => $build('en'), 'sort_order' => 2, 'is_published' => true,
        ];
    }

    private function statBuilds(): array
    {
        $build = fn (string $loc) => $loc === 'vi' ? <<<'HTML'
<p>Mỗi khi lên cấp, nhân vật nhận điểm để cộng vào các chỉ số. Cộng đúng giúp bạn mạnh hơn và tiết kiệm reset.</p>
<figure class="guide-fig" style="max-width:360px;margin-left:auto;margin-right:auto"><img src="/images/guides/stats-1-charwindow.jpg" loading="lazy" alt="Cửa sổ nhân vật"><figcaption>Cửa sổ nhân vật (phím C): 4 chỉ số STR/AGI/STA/ENG, số điểm chưa cộng (Point) và nút + để cộng. Dưới mỗi chỉ số là hiệu quả của nó.</figcaption></figure>
<h2>4 chỉ số cơ bản</h2>
<ul>
<li><strong>Strength (Sức mạnh)</strong>: tăng sát thương vật lý; cũng là chỉ số cần để mặc được đồ nặng. Cốt lõi của Dark Knight, Rage Fighter, một phần Magic Gladiator.</li>
<li><strong>Agility (Nhanh nhẹn)</strong>: tăng thủ, tốc đánh, sát thương cung. Cốt lõi của Fairy Elf.</li>
<li><strong>Vitality (Thể lực)</strong>: tăng máu (HP). Cần cho PvP và trụ lâu khi farm.</li>
<li><strong>Energy (Năng lượng)</strong>: tăng sát thương phép. Cốt lõi của Dark Wizard, Summoner, Magic Gladiator hệ phép.</li>
<li><strong>Command (Mệnh lệnh)</strong>: chỉ Dark Lord có, tăng sát thương thú cưỡi và triệu hồi.</li>
</ul>
<h2>Nguyên tắc cộng điểm</h2>
<ol>
<li>Cộng đủ chỉ số phụ để mặc được đồ mục tiêu, phần còn lại dồn vào chỉ số sát thương chính.</li>
<li>Giai đoạn cày cấp: ưu tiên chỉ số sát thương để giết quái nhanh.</li>
<li>Giai đoạn PvP: dồn thêm Vitality để đủ máu.</li>
</ol>
<div class="guide-note">Định hướng build theo kinh nghiệm Season 6. GM có thể tinh chỉnh cho hợp máy chủ.</div>
<p>Chọn class ở mục <strong>Hướng dẫn theo class</strong> để xem build chi tiết.</p>
HTML
            : <<<'HTML'
<p>Every level-up gives points to spend on stats. Spending them well makes you stronger and saves resets.</p>
<figure class="guide-fig" style="max-width:360px;margin-left:auto;margin-right:auto"><img src="/images/guides/stats-1-charwindow.jpg" loading="lazy" alt="Character window"><figcaption>The character window (C key): the 4 stats STR/AGI/STA/ENG, unspent Points and the + button. Each stat's effect is listed below it.</figcaption></figure>
<h2>The 4 core stats</h2>
<ul>
<li><strong>Strength</strong>: raises physical damage; it is also the stat you need to wear heavy gear. Core for Dark Knight, Rage Fighter, and physical Magic Gladiator.</li>
<li><strong>Agility</strong>: raises defense, attack speed and bow damage. Core for Fairy Elf.</li>
<li><strong>Vitality</strong>: raises HP. Needed for PvP and long farming.</li>
<li><strong>Energy</strong>: raises magic damage. Core for Dark Wizard, Summoner and magic Magic Gladiator.</li>
<li><strong>Command</strong>: Dark Lord only, raises pet and summon damage.</li>
</ul>
<h2>Allocation principles</h2>
<ol>
<li>Add just enough secondary stats to wear your target gear, put the rest into your main damage stat.</li>
<li>While leveling: prioritize your damage stat to kill fast.</li>
<li>For PvP: add Vitality for survivability.</li>
</ol>
<div class="guide-note">Build guidance follows Season 6 conventions. GMs may tune it for the server.</div>
<p>Pick your class under <strong>Class guides</strong> for a detailed build.</p>
HTML;

        return [
            'slug' => 'cong-diem-cac-class', 'category' => 'stats', 'class_key' => null, 'icon' => 'chart-simple',
            'title_vi' => 'Cách cộng điểm hiệu quả', 'title_en' => 'Effective stat builds',
            'excerpt_vi' => 'Ý nghĩa 4 chỉ số và nguyên tắc cộng điểm chung.',
            'excerpt_en' => 'What the core stats do and general allocation principles.',
            'body_vi' => $build('vi'), 'body_en' => $build('en'), 'sort_order' => 1, 'is_published' => true,
        ];
    }

    // -------------------------------------------------------------- General --

    /** /offstore: keep the personal store open while offline (illustrated walkthrough). */
    private function offstoreGuide(): array
    {
        $build = function (string $loc) {
            $x = $loc === 'vi' ? [
                'lead' => '<code>/offstore</code> cho phép <strong>nhân vật đứng bán hàng ngay cả khi bạn tắt game</strong>. '
                    . 'Đây là 1 trong 2 chế độ treo máy offline của muss6; chế độ còn lại là '
                    . '<a href="/huong-dan/luyen-cap-offline">luyện cấp offline (/offlevel)</a>.',
                'h_store' => 'Các bước treo shop',
                'store_steps' => [
                    'Xếp đồ muốn bán vào <strong>cửa hàng cá nhân</strong>, đặt giá từng món, đặt tên shop rồi <strong>mở shop</strong> như bình thường.',
                    'Gõ <code>/offstore</code> vào khung chat. Thấy dòng thông báo xanh <em>"Offline store started. You can log back in at any time to stop it."</em> là thành công.',
                    'Game sẽ tự ngắt kết nối, bạn <strong>đóng game được luôn</strong>. Nhân vật đứng nguyên tại chỗ và tiếp tục bán hàng.',
                ],
                'store_notes' => [
                    '<strong>Miễn phí</strong>, không tốn zen.',
                    'Phải mở shop trước rồi mới gõ lệnh, chưa mở shop thì lệnh sẽ báo nhắc mở shop.',
                    '<strong>Bán hết hàng</strong>: shop tự đóng, nhân vật tự thoát và toàn bộ zen bán được <strong>lưu ngay</strong> vào nhân vật.',
                    'Người mua chỉ cần bấm vào biển hiệu shop trên đầu nhân vật để xem và mua như shop thường.',
                    'Mẹo: đứng bán ở chỗ đông người qua lại (quảng trường Lorencia) để dễ bán.',
                ],
                'h_level' => 'Luyện cấp offline: /offlevel',
                'level_steps' => [
                    'Bật <strong>MU Helper</strong> (phím <strong>Home</strong>), chỉnh cấu hình đánh quái / nhặt đồ / buff và để nhân vật train ổn định một lúc.',
                    'Gõ <code>/offlevel</code> vào khung chat. Nhân vật chuyển sang chế độ offline và tiếp tục tự đánh quái theo đúng cấu hình MU Helper.',
                    'Đóng game. Nhân vật vẫn luyện cấp, nhặt đồ, dùng buff và tự hồi máu trên server.',
                ],
                'level_notes' => [
                    '<strong>Có phí zen</strong>: thu một khoản khi kích hoạt (tăng theo cấp nhân vật) và trừ dần trong lúc treo, giống phí MU Helper thông thường.',
                    'Yêu cầu MU Helper đang chạy tại thời điểm gõ lệnh.',
                    'Nếu nhân vật <strong>bị chết</strong>, nhân vật sẽ hồi sinh rồi phiên offline tự dừng (tránh chết lặp tốn đồ).',
                ],
                'h_common' => 'Lưu ý chung khi treo máy offline',
                'common' => [
                    'Mỗi tài khoản chỉ chạy được <strong>1 phiên offline</strong> tại một thời điểm.',
                    '<strong>Đăng nhập lại</strong> tài khoản bất cứ lúc nào để dừng phiên offline, tiến độ (zen, exp, đồ) được giữ nguyên.',
                    'Khi server bảo trì hoặc khởi động lại, phiên offline sẽ dừng, bạn cần vào game bật lại.',
                    'Cần <strong>client mới nhất</strong>: chỉ cần mở game qua Launcher là tự cập nhật.',
                ],
                'cap_shop' => 'Mở cửa hàng cá nhân, đặt giá và đặt tên shop (ví dụ "Ban Ngoc Re") trước khi gõ lệnh.',
                'cap_command' => 'Gõ /offstore: hiện dòng chữ xanh "Offline store started. You can log back in at any time to stop it."',
                'h_buy' => 'Người khác mua hàng của bạn thế nào',
                'buy_intro' => 'Sau khi bạn tắt game, nhân vật vẫn đứng trên map kèm biển shop. Người mua thao tác y như một shop bình thường:',
                'cap_ghost' => 'Người mua vẫn thấy nhân vật bạn đứng bán, dù bạn đã thoát game.',
                'cap_window' => 'Bấm vào shop để mở, xem danh sách món và giá.',
                'cap_buy' => 'Bấm món cần mua rồi xác nhận ở hộp thoại "Do you want to buy an item?".',
                'cap_soldout' => 'Bán hết hàng: shop tự đóng, nhân vật tự thoát, toàn bộ zen đã vào túi.',
                'cols' => ['', '/offstore', '/offlevel'],
                'rows' => [
                    ['Công dụng', 'Đứng bán hàng trong shop cá nhân', 'Tự đánh quái luyện cấp theo MU Helper'],
                    ['Điều kiện', 'Shop cá nhân đang mở', 'MU Helper đang chạy'],
                    ['Chi phí', 'Miễn phí', 'Phí zen theo cấp + trừ dần khi treo'],
                    ['Tự dừng khi', 'Bán hết hàng (zen lưu ngay)', 'Nhân vật chết (sau khi hồi sinh)'],
                    ['Dừng thủ công', 'Đăng nhập lại', 'Đăng nhập lại'],
                ],
            ] : [
                'lead' => '<code>/offstore</code> lets your <strong>character keep selling even after you close the game</strong>. '
                    . 'It is one of muss6\'s two offline modes; the other is '
                    . '<a href="/en/huong-dan/luyen-cap-offline">offline leveling (/offlevel)</a>.',
                'h_store' => 'Step by step',
                'store_steps' => [
                    'Put the items into your <strong>personal store</strong>, set a price on each, name the store and <strong>open it</strong> as usual.',
                    'Type <code>/offstore</code> in chat. The blue message <em>"Offline store started. You can log back in at any time to stop it."</em> confirms it worked.',
                    'The game disconnects itself and you can <strong>close the client</strong>. Your character keeps selling on the spot.',
                ],
                'store_notes' => [
                    '<strong>Free</strong>, costs no zen.',
                    'The store must already be open when you type the command, otherwise it reminds you to open it first.',
                    '<strong>Sold out</strong>: the store closes, the character logs out and all earned zen is <strong>saved immediately</strong>.',
                    'Buyers just click the store sign above your head and buy like from any normal store.',
                    'Tip: park in a busy spot (Lorencia square) to sell faster.',
                ],
                'h_level' => 'Offline leveling: /offlevel',
                'level_steps' => [
                    'Start <strong>MU Helper</strong> (<strong>Home</strong> key), tune its attack / loot / buff settings and let it grind stably for a bit.',
                    'Type <code>/offlevel</code> in chat. The character switches to offline mode and keeps fighting with your MU Helper settings.',
                    'Close the client. The character keeps leveling, looting, buffing and healing on the server.',
                ],
                'level_notes' => [
                    '<strong>Costs zen</strong>: an activation fee (scales with character level) plus the usual ongoing MU Helper zen drain.',
                    'MU Helper must be running when you type the command.',
                    'If the character <strong>dies</strong>, it respawns and the offline session stops (no repeated deaths).',
                ],
                'h_common' => 'General offline-mode notes',
                'common' => [
                    'Each account can run only <strong>one offline session</strong> at a time.',
                    '<strong>Log back in</strong> at any time to stop the session, all progress (zen, exp, items) is kept.',
                    'A server restart or maintenance stops the session, just log in and start it again.',
                    'Requires the <strong>latest client</strong>: simply start the game through the Launcher and it updates itself.',
                ],
                'cap_shop' => 'Open your personal store, set prices and a store name (e.g. "Ban Ngoc Re") before typing the command.',
                'cap_command' => 'Type /offstore: the blue line "Offline store started. You can log back in at any time to stop it." appears.',
                'h_buy' => 'How other players buy from you',
                'buy_intro' => 'After you close the game, your character stays on the map with its store sign. Buyers use it like any normal store:',
                'cap_ghost' => 'Buyers still see your character standing and selling, even though you left the game.',
                'cap_window' => 'They click the store to open it and browse the items and prices.',
                'cap_buy' => 'They click an item and confirm on the "Do you want to buy an item?" dialog.',
                'cap_soldout' => 'Sold out: the store closes, the character logs out, and all zen is already in the bag.',
                'cols' => ['', '/offstore', '/offlevel'],
                'rows' => [
                    ['Purpose', 'Sell from your personal store', 'Grind monsters with MU Helper'],
                    ['Requires', 'Personal store open', 'MU Helper running'],
                    ['Cost', 'Free', 'Level-based zen fee + ongoing drain'],
                    ['Auto-stops when', 'Sold out (zen saved immediately)', 'Character dies (after respawn)'],
                    ['Manual stop', 'Log back in', 'Log back in'],
                ],
            ];

            $ol = function (array $items) {
                $li = '';
                foreach ($items as $i) {
                    $li .= '<li>' . $i . '</li>';
                }

                return '<ol>' . $li . '</ol>';
            };
            $ul = function (array $items) {
                $li = '';
                foreach ($items as $i) {
                    $li .= '<li>' . $i . '</li>';
                }

                return '<ul>' . $li . '</ul>';
            };

            $fig = function (string $file, string $caption) {
                return '<figure class="guide-fig"><img src="/images/guides/' . $file . '" loading="lazy" alt="' . $caption . '">'
                    . '<figcaption>' . $caption . '</figcaption></figure>';
            };

            $rows = '';
            foreach ($x['rows'] as $r) {
                $rows .= "<tr><td><strong>{$r[0]}</strong></td><td>{$r[1]}</td><td>{$r[2]}</td></tr>";
            }
            $table = '<div class="table-responsive"><table><thead><tr><th>' . $x['cols'][0] . '</th><th>'
                . $x['cols'][1] . '</th><th>' . $x['cols'][2] . '</th></tr></thead><tbody>' . $rows . '</tbody></table></div>';

            return "<p>{$x['lead']}</p>"
                . "<h2>{$x['h_store']}</h2>" . $ol($x['store_steps'])
                . $fig('offstore-1-shop.jpg', $x['cap_shop'])
                . $fig('offstore-2-command.jpg', $x['cap_command'])
                . $ul($x['store_notes'])
                . "<h3>{$x['h_buy']}</h3><p>{$x['buy_intro']}</p>"
                . $fig('offstore-3-ghost.jpg', $x['cap_ghost'])
                . $fig('offstore-4-shopwindow.jpg', $x['cap_window'])
                . $fig('offstore-5-buy.jpg', $x['cap_buy'])
                . $fig('offstore-6-soldout.jpg', $x['cap_soldout'])
                . "<h2>{$x['h_common']}</h2>" . '<div class="guide-note">' . $ul($x['common']) . '</div>'
                . $table;
        };

        return [
            'slug' => 'treo-may-offline', 'category' => 'feature', 'class_key' => null, 'icon' => 'store',
            'title_vi' => 'Treo shop offline (/offstore)',
            'title_en' => 'Offline store (/offstore)',
            'excerpt_vi' => 'Tắt game mà nhân vật vẫn đứng bán hàng trong shop cá nhân, kèm ảnh từng bước.',
            'excerpt_en' => 'Close the game while your character keeps selling from its personal store, step by step.',
            'body_vi' => $build('vi'), 'body_en' => $build('en'), 'sort_order' => 3, 'is_published' => true,
        ];
    }

    /** /offlevel: keep grinding with the MU Helper while offline. */
    private function offlevelGuide(): array
    {
        $build = function (string $loc) {
            $vi = $loc === 'vi';
            $lead = $vi
                ? '<code>/offlevel</code> cho phép <strong>nhân vật tự đánh quái luyện cấp ngay cả khi bạn tắt game</strong>, '
                    . 'theo đúng cấu hình MU Helper của bạn. Chế độ còn lại là '
                    . '<a href="/huong-dan/treo-may-offline">treo shop offline (/offstore)</a>.'
                : '<code>/offlevel</code> lets your <strong>character keep grinding monsters even after you close the game</strong>, '
                    . 'using your MU Helper settings. The other offline mode is '
                    . '<a href="/en/huong-dan/treo-may-offline">offline store (/offstore)</a>.';
            $hSteps = $vi ? 'Các bước treo luyện cấp' : 'Step by step';
            $steps = $vi ? [
                'Bật <strong>MU Helper</strong> (phím <strong>Home</strong>), chỉnh cấu hình đánh quái / nhặt đồ / buff và để nhân vật train ổn định một lúc.',
                'Gõ <code>/offlevel</code> vào khung chat. Nhân vật chuyển sang chế độ offline và tiếp tục tự đánh quái theo đúng cấu hình MU Helper.',
                'Đóng game. Nhân vật vẫn luyện cấp, nhặt đồ, dùng buff và tự hồi máu trên server.',
            ] : [
                'Start <strong>MU Helper</strong> (<strong>Home</strong> key), tune its attack / loot / buff settings and let it grind stably for a bit.',
                'Type <code>/offlevel</code> in chat. The character switches to offline mode and keeps fighting with your MU Helper settings.',
                'Close the client. The character keeps leveling, looting, buffing and healing on the server.',
            ];
            $hNotes = $vi ? 'Lưu ý' : 'Notes';
            $notes = $vi ? [
                '<strong>Có phí zen</strong>: thu một khoản khi kích hoạt (tăng theo cấp nhân vật) và trừ dần trong lúc treo, giống phí MU Helper thông thường.',
                'Yêu cầu MU Helper đang chạy tại thời điểm gõ lệnh.',
                'Nếu nhân vật <strong>bị chết</strong>, nhân vật sẽ hồi sinh rồi phiên offline tự dừng (tránh chết lặp tốn đồ).',
                'Chọn bãi quái vừa sức để không chết, và còn đủ zen để trả phí treo.',
            ] : [
                '<strong>Costs zen</strong>: an activation fee (scales with character level) plus the usual ongoing MU Helper zen drain.',
                'MU Helper must be running when you type the command.',
                'If the character <strong>dies</strong>, it respawns and the offline session stops (no repeated deaths).',
                'Pick a spot your character can survive, and keep enough zen for the ongoing fee.',
            ];
            $hCommon = $vi ? 'Lưu ý chung cho treo máy offline' : 'General offline-mode notes';
            $common = $vi ? [
                'Mỗi tài khoản chỉ chạy được <strong>1 phiên offline</strong> tại một thời điểm.',
                '<strong>Đăng nhập lại</strong> tài khoản bất cứ lúc nào để dừng, tiến độ (exp, zen, đồ) được giữ nguyên.',
                'Khi server bảo trì hoặc khởi động lại, phiên offline sẽ dừng, bạn cần vào game bật lại.',
                'Cần <strong>client mới nhất</strong>: chỉ cần mở game qua Launcher là tự cập nhật.',
            ] : [
                'Each account can run only <strong>one offline session</strong> at a time.',
                '<strong>Log back in</strong> at any time to stop it, all progress (exp, zen, items) is kept.',
                'A server restart or maintenance stops the session, just log in and start it again.',
                'Requires the <strong>latest client</strong>: simply start the game through the Launcher and it updates itself.',
            ];

            $figCap = $vi
                ? 'Cửa sổ cấu hình MU Helper (mở bằng phím Z): chỉnh phạm vi đánh, kỹ năng, buff, nhặt đồ. Bật/tắt MU Helper bằng phím Home.'
                : 'The MU Helper config window (open with Z): set attack range, skills, buffs and looting. Toggle MU Helper with the Home key.';
            $fig = '<figure class="guide-fig"><img src="/images/guides/offlevel-1-muhelper.jpg" loading="lazy" alt="' . $figCap . '">'
                . '<figcaption>' . $figCap . '</figcaption></figure>';

            return "<p>{$lead}</p><h2>{$hSteps}</h2>" . $this->olHtml($steps) . $fig . $this->ulHtml($notes)
                . "<h2>{$hCommon}</h2>" . $this->noteBox($this->ulHtml($common));
        };

        return [
            'slug' => 'luyen-cap-offline', 'category' => 'feature', 'class_key' => null, 'icon' => 'moon',
            'title_vi' => 'Luyện cấp offline (/offlevel)',
            'title_en' => 'Offline leveling (/offlevel)',
            'excerpt_vi' => 'Tắt game mà nhân vật vẫn tự đánh quái luyện cấp theo MU Helper.',
            'excerpt_en' => 'Close the game while your character keeps grinding with the MU Helper.',
            'body_vi' => $build('vi'), 'body_en' => $build('en'), 'sort_order' => 4, 'is_published' => true,
        ];
    }

    // ----------------------------------------------------- Content helpers --

    private function olHtml(array $items): string
    {
        return '<ol>' . implode('', array_map(static fn ($i) => "<li>$i</li>", $items)) . '</ol>';
    }

    private function ulHtml(array $items): string
    {
        return '<ul>' . implode('', array_map(static fn ($i) => "<li>$i</li>", $items)) . '</ul>';
    }

    private function noteBox(string $inner): string
    {
        return '<div class="guide-note">' . $inner . '</div>';
    }

    // ------------------------------------------------------ Getting started --

    private function gettingStartedGuide(): array
    {
        $build = function (string $loc) {
            $vi = $loc === 'vi';
            $x = $vi ? [
                'lead' => 'Chào mừng đến với muss6! Chỉ cần 3 bước để bắt đầu chơi.',
                'h1' => 'Bước 1: Đăng ký tài khoản',
                's1' => 'Bấm nút <strong>Đăng ký</strong> ở góc trên bên phải trang web. Điền <strong>Tên đăng nhập</strong>, '
                    . '<strong>Email</strong>, <strong>Mật khẩu</strong> và <strong>Mã bảo mật</strong>. '
                    . 'Hãy nhớ kỹ Mã bảo mật: nó dùng để xác nhận khi xoá nhân vật sau này.',
                'h2' => 'Bước 2: Tải và cài game',
                's2' => 'Vào trang <a href="/tai-game">Tải game</a>, tải bộ cài 1-Click (<code>muss6-setup.exe</code>), chạy file, '
                    . 'chọn thư mục rồi bấm cài. Bộ cài đã <strong>thiết lập sẵn máy chủ</strong>, bạn không cần chỉnh IP hay file cấu hình.',
                'h3' => 'Bước 3: Vào game',
                's3' => 'Mở game bằng <strong>Launcher (muss6)</strong>. Lần đầu, Launcher sẽ tự tải và cập nhật dữ liệu game '
                    . '(khoảng 1&nbsp;GB, chỉ 1 lần). Sau đó đăng nhập bằng tài khoản vừa tạo, tạo nhân vật và bắt đầu chơi.',
                'req_h' => 'Yêu cầu hệ thống',
                'req' => [
                    'Windows 10 hoặc 11 (64-bit)',
                    'Khoảng 2&nbsp;GB dung lượng trống',
                    'Kết nối internet (cho lần cập nhật dữ liệu đầu tiên)',
                ],
                'next' => 'Tiếp theo: <a href="/huong-dan/quan-ly-nhan-vat">Quản lý nhân vật (reset, gỡ kẹt, đổi tên)</a> '
                    . 'và <a href="/huong-dan/cong-diem-cac-class">Cách cộng điểm</a>.',
            ] : [
                'lead' => 'Welcome to muss6! Just 3 steps to start playing.',
                'h1' => 'Step 1: Register an account',
                's1' => 'Click <strong>Register</strong> at the top-right of the site. Fill in your <strong>login name</strong>, '
                    . '<strong>email</strong>, <strong>password</strong> and a <strong>security code</strong>. '
                    . 'Remember the security code: it is required to confirm character deletion later.',
                'h2' => 'Step 2: Download and install',
                's2' => 'Go to the <a href="/en/tai-game">Download</a> page, get the 1-Click installer (<code>muss6-setup.exe</code>), '
                    . 'run it, pick a folder and install. The installer is <strong>pre-configured for the server</strong>, no IP or config editing.',
                'h3' => 'Step 3: Launch the game',
                's3' => 'Open the game with the <strong>Launcher (muss6)</strong>. On first run it auto-downloads and updates the game '
                    . 'data (about 1&nbsp;GB, once). Then log in with your new account, create a character and start playing.',
                'req_h' => 'System requirements',
                'req' => [
                    'Windows 10 or 11 (64-bit)',
                    'About 2&nbsp;GB of free space',
                    'Internet connection (for the first data update)',
                ],
                'next' => 'Next: <a href="/en/huong-dan/quan-ly-nhan-vat">Character management (reset, unstick, rename)</a> '
                    . 'and <a href="/en/huong-dan/cong-diem-cac-class">Stat builds</a>.',
            ];

            return "<p>{$x['lead']}</p>"
                . "<h2>{$x['h1']}</h2><p>{$x['s1']}</p>"
                . "<h2>{$x['h2']}</h2><p>{$x['s2']}</p>"
                . "<h2>{$x['h3']}</h2><p>{$x['s3']}</p>"
                . "<h2>{$x['req_h']}</h2>" . $this->ulHtml($x['req'])
                . "<p>{$x['next']}</p>";
        };

        return [
            'slug' => 'dang-ky-cai-dat', 'category' => 'start', 'class_key' => null, 'icon' => 'flag-checkered',
            'title_vi' => 'Đăng ký & cài đặt game', 'title_en' => 'Register & install the game',
            'excerpt_vi' => '3 bước để có tài khoản, cài game và vào chơi muss6.',
            'excerpt_en' => 'Three steps to register, install and start playing muss6.',
            'body_vi' => $build('vi'), 'body_en' => $build('en'), 'sort_order' => 1, 'is_published' => true,
        ];
    }

    private function characterManagementGuide(): array
    {
        $build = function (string $loc) {
            $vi = $loc === 'vi';
            $x = $vi ? [
                'lead' => 'Sau khi đăng nhập website, vào mục <strong>Nhân vật</strong> để quản lý nhân vật của bạn.',
                'note' => '<strong>Quan trọng:</strong> mọi thao tác dưới đây yêu cầu nhân vật đang <strong>offline</strong> (đã thoát game). '
                    . 'Nếu nhân vật còn online, hệ thống sẽ báo lỗi.',
                'items' => [
                    ['Reset nhân vật', 'Khi đạt <strong>cấp 400</strong> (cấp tối đa), bạn có thể Reset: nhân vật về <strong>cấp 1</strong> nhưng '
                        . '<strong>giữ nguyên trang bị và điểm đã cộng</strong>. Reset <strong>không giới hạn số lần</strong>; số lần reset '
                        . 'hiển thị trên <a href="/rankings">bảng xếp hạng</a>.'],
                    ['Xoá PK', 'Nếu nhân vật giết người chơi khác nhiều lần sẽ thành <strong>Player Killer (PK)</strong> và bị hạn chế '
                        . '(một số NPC/sự kiện không cho vào, dễ bị lính canh và người chơi khác tấn công). Dùng <strong>Xoá PK</strong> để về trạng thái bình thường.'],
                    ['Về thị trấn', 'Nếu nhân vật bị kẹt hoặc không vào được game, dùng <strong>Về thị trấn</strong> để đưa nhân vật về Lorencia an toàn.'],
                    ['Đổi tên', 'Đổi tên nhân vật, tối đa <strong>10 ký tự</strong> (chữ và số).'],
                    ['Xoá nhân vật', 'Cần nhập <strong>Mã bảo mật</strong> (đã đặt khi đăng ký). Thao tác này <strong>không hoàn tác được</strong>, hãy cân nhắc kỹ.'],
                ],
            ] : [
                'lead' => 'After logging into the website, open the <strong>Characters</strong> section to manage your characters.',
                'note' => '<strong>Important:</strong> every action below requires the character to be <strong>offline</strong> (logged out of the game). '
                    . 'If the character is still online, the action is rejected.',
                'items' => [
                    ['Reset character', 'Once you reach <strong>level 400</strong> (max level) you can reset: the character goes back to '
                        . '<strong>level 1</strong> but <strong>keeps its gear and added stat points</strong>. Resets are <strong>unlimited</strong>; '
                        . 'your reset count shows on the <a href="/en/rankings">rankings</a>.'],
                    ['Clear PK', 'Killing other players repeatedly turns your character into a <strong>Player Killer (PK)</strong>, which is restricted '
                        . '(barred from some NPCs/events, attacked by guards and players). Use <strong>Clear PK</strong> to return to normal.'],
                    ['Move to town', 'If your character gets stuck or cannot enter the game, use <strong>Move to town</strong> to warp it back to safe Lorencia.'],
                    ['Rename', 'Rename the character, up to <strong>10 characters</strong> (letters and digits).'],
                    ['Delete character', 'Requires your <strong>security code</strong> (set at registration). This <strong>cannot be undone</strong>, so be careful.'],
                ],
            ];

            $sections = '';
            foreach ($x['items'] as $it) {
                $sections .= "<h2>{$it[0]}</h2><p>{$it[1]}</p>";
            }

            return "<p>{$x['lead']}</p>" . $this->noteBox($x['note']) . $sections;
        };

        return [
            'slug' => 'quan-ly-nhan-vat', 'category' => 'start', 'class_key' => null, 'icon' => 'user-gear',
            'title_vi' => 'Quản lý nhân vật: reset, xoá PK, gỡ kẹt, đổi tên',
            'title_en' => 'Character management: reset, clear PK, unstick, rename',
            'excerpt_vi' => 'Reset, xoá PK, về thị trấn, đổi tên và xoá nhân vật ngay trên website.',
            'excerpt_en' => 'Reset, clear PK, move to town, rename and delete your character on the website.',
            'body_vi' => $build('vi'), 'body_en' => $build('en'), 'sort_order' => 2, 'is_published' => true,
        ];
    }

    // -------------------------------------------------------------- Events --

    private function eventsGuide(): array
    {
        // [level, characterLevelRange] per event, from the server's Season 6 config.
        $bloodTiers = [[1, '15-80'], [2, '81-130'], [3, '131-180'], [4, '181-230'], [5, '231-280'], [6, '281-330'], [7, '331-400']];
        $devilTiers = [[1, '15-130'], [2, '131-180'], [3, '181-230'], [4, '231-280'], [5, '281-330'], [6, '331-400']];
        $chaosTiers = [[1, '15-49'], [2, '50-119'], [3, '120-179'], [4, '180-239'], [5, '240-299'], [6, '300-400']];

        $build = function (string $loc) use ($bloodTiers, $devilTiers, $chaosTiers) {
            $vi = $loc === 'vi';

            $tierTable = function (array $tiers, string $lvlHdr, string $rangeHdr) {
                $rows = '';
                foreach ($tiers as $t) {
                    $rows .= "<tr><td>{$t[0]}</td><td>{$t[1]}</td></tr>";
                }

                return '<div class="table-responsive"><table><thead><tr><th>' . $lvlHdr . '</th><th>' . $rangeHdr
                    . '</th></tr></thead><tbody>' . $rows . '</tbody></table></div>';
            };

            $x = $vi ? [
                'lead' => 'muss6 có nhiều sự kiện chạy tự động theo lịch. Xem <strong>giờ chính xác</strong> (theo múi giờ của bạn) ở trang '
                    . '<a href="/events">Sự kiện</a>. Bài này giải thích cách chơi 3 sự kiện vào-cửa phổ biến.',
                'h_common' => 'Cách vào sự kiện (chung)',
                'common' => [
                    'Cần có <strong>vé</strong> tương ứng của sự kiện trong túi đồ (mua ở NPC hoặc rơi từ quái).',
                    'Nhân vật phải <strong>đúng khoảng cấp</strong> của cấp độ sự kiện bạn muốn vào (xem bảng bên dưới).',
                    'Chỉ vào được <strong>trong khung giờ mở cửa</strong>. Quá giờ sẽ báo "đã hết giờ vào".',
                    '<strong>Chuột phải vào vé</strong> để hiện danh sách cấp độ, rồi chọn cấp phù hợp với nhân vật.',
                ],
                'lvl_hdr' => 'Cấp sự kiện', 'range_hdr' => 'Cấp nhân vật',
                'l_howto' => 'Cách chơi', 'l_info' => 'Thông tin', 'l_rewards' => 'Phần thưởng',
                'bc_h' => 'Blood Castle (Quỷ Thành)',
                'bc_goal' => '<strong>Mục tiêu (phối hợp):</strong> cả nhóm cùng phá <strong>Cổng Lâu Đài</strong> rồi tiêu diệt <strong>Tượng Pha Lê</strong> trước khi hết giờ.',
                'bc_howto' => [
                    'Mọi người vào <strong>cùng một phe</strong>, phối hợp làm nhiệm vụ trong 20 phút.',
                    'Đánh đủ quái canh để <strong>mở cầu</strong>, rồi phá <strong>Cổng Lâu Đài</strong>.',
                    'Băng qua cầu vào trong, tấn công <strong>Tượng Pha Lê (Crystal Statue)</strong> để hoàn thành.',
                    'Hoàn thành <strong>càng sớm, thưởng càng cao</strong> (có thưởng cộng theo thời gian còn lại).',
                ],
                'bc_facts' => ['<strong>Vé:</strong> Invisibility Cloak (Áo tàng hình)', '<strong>Lịch:</strong> mỗi 2 giờ', '<strong>Thời gian:</strong> mở cửa 1 phút, chơi 20 phút', '<strong>Phí:</strong> miễn phí'],
                'bc_rewards' => [
                    '<strong>Kinh nghiệm</strong> khi phá cổng và giết Tượng, cộng thưởng hoàn thành và thưởng theo giây còn lại.',
                    '<strong>zen</strong>: người thắng 20.000-250.000, người thua 10.000-120.000 (tuỳ cấp sự kiện).',
                    '<strong>Ngọc Hỗn Nguyên (Jewel of Chaos)</strong>: rơi 90% cho người hoàn thành.',
                ],
                'ds_h' => 'Devil Square (Quảng Trường Quỷ)',
                'ds_goal' => '<strong>Mục tiêu (xếp hạng):</strong> diệt thật nhiều quái để đạt thứ hạng cao nhất.',
                'ds_howto' => [
                    'Đây là sự kiện <strong>xếp hạng</strong>: diệt càng nhiều quái, hạng của bạn càng cao.',
                    'Quái ra theo <strong>4 đợt</strong> tăng dần độ khó (đợt cuối có boss). Cày liên tục suốt 20 phút.',
                    'Hết giờ, hệ thống xếp hạng người chơi và trao thưởng theo thứ hạng.',
                ],
                'ds_facts' => ['<strong>Vé:</strong> Devil\'s Invitation (Lời mời của Quỷ)', '<strong>Lịch:</strong> mỗi 4 giờ', '<strong>Thời gian:</strong> mở cửa 1 phút, chơi 20 phút', '<strong>Phí:</strong> miễn phí'],
                'ds_rewards' => [
                    'Thưởng theo <strong>thứ hạng (1-4)</strong>: hạng càng cao, kinh nghiệm và zen càng nhiều.',
                    '<strong>Kinh nghiệm</strong> và <strong>zen</strong> (hạng 1 nhận nhiều nhất, zen tới ~150.000 ở cấp cao).',
                ],
                'cc_h' => 'Chaos Castle (Hỗn Nguyên Lâu)',
                'cc_goal' => '<strong>Mục tiêu (sinh tồn PvP):</strong> người <strong>sống sót cuối cùng</strong> là người thắng.',
                'cc_howto' => [
                    'Sự kiện <strong>sinh tồn PvP, không tổ đội</strong>: mọi người là đối thủ của nhau.',
                    'Vừa đánh quái để mở khu vực (qua các giai đoạn), vừa <strong>hạ hoặc né</strong> người chơi khác.',
                    '<strong>Địa hình thu hẹp dần</strong> qua từng giai đoạn, dồn mọi người lại gần nhau; cẩn thận bị đẩy rớt khỏi rìa.',
                    'Người <strong>sống sót cuối cùng</strong> (hoặc điểm cao nhất) chiến thắng.',
                ],
                'cc_facts' => ['<strong>Vé:</strong> Armor of Guardsman (Giáp Vệ Binh)', '<strong>Lịch:</strong> mỗi 1 giờ', '<strong>Thời gian:</strong> mở cửa 5 phút, chơi 10 phút', '<strong>Phí vào:</strong> tăng theo cấp, từ 25.000 zen (cấp 1) đến 650.000 zen (cấp 6)'],
                'cc_rewards' => [
                    '<strong>Nhiều loại ngọc</strong> cho người thắng (90%): Ngọc Hỗn Nguyên (Chaos), Phúc (Bless), Tâm Linh (Soul), Sáng Tạo (Creation), Sự Sống (Life).',
                    'Cơ hội nhận <strong>đồ thần (Ancient)</strong> ở các cấp sự kiện cao hơn.',
                ],
            ] : [
                'lead' => 'muss6 runs several scheduled events. See the <strong>exact times</strong> (in your timezone) on the '
                    . '<a href="/en/events">Events</a> page. This guide explains the 3 common entry-based events.',
                'h_common' => 'How to enter an event (general)',
                'common' => [
                    'You need the event\'s <strong>ticket</strong> in your inventory (bought from an NPC or dropped by monsters).',
                    'Your character must be in the <strong>right level range</strong> for the event level you want (see tables below).',
                    'You can only enter <strong>during the open window</strong>. After that it says "the time to enter has passed".',
                    '<strong>Right-click the ticket</strong> to list the levels, then pick the one matching your character.',
                ],
                'lvl_hdr' => 'Event level', 'range_hdr' => 'Character level',
                'l_howto' => 'How to play', 'l_info' => 'Info', 'l_rewards' => 'Rewards',
                'bc_h' => 'Blood Castle',
                'bc_goal' => '<strong>Goal (cooperative):</strong> the whole group breaks the <strong>Castle Gate</strong> then destroys the <strong>Crystal Statue</strong> before time runs out.',
                'bc_howto' => [
                    'Everyone enters on the <strong>same side</strong> and works together within 20 minutes.',
                    'Kill enough guard monsters to <strong>open the bridge</strong>, then break the <strong>Castle Gate</strong>.',
                    'Cross the bridge and attack the <strong>Crystal Statue</strong> to finish.',
                    'The <strong>faster you finish, the bigger the reward</strong> (there is a bonus for remaining time).',
                ],
                'bc_facts' => ['<strong>Ticket:</strong> Invisibility Cloak', '<strong>Schedule:</strong> every 2 hours', '<strong>Timing:</strong> 1-minute entry, 20-minute run', '<strong>Fee:</strong> free'],
                'bc_rewards' => [
                    '<strong>Experience</strong> for breaking the gate and killing the Statue, plus a completion bonus and a per-second bonus.',
                    '<strong>zen</strong>: winners 20,000-250,000, losers 10,000-120,000 (by event level).',
                    '<strong>Jewel of Chaos</strong>: 90% drop for those who complete it.',
                ],
                'ds_h' => 'Devil Square',
                'ds_goal' => '<strong>Goal (ranking):</strong> kill as many monsters as possible to reach the highest rank.',
                'ds_howto' => [
                    'This is a <strong>ranking</strong> event: more kills means a higher rank.',
                    'Monsters come in <strong>4 waves</strong> of rising difficulty (bosses in the last). Grind non-stop for 20 minutes.',
                    'At the end, players are ranked and rewarded by rank.',
                ],
                'ds_facts' => ['<strong>Ticket:</strong> Devil\'s Invitation', '<strong>Schedule:</strong> every 4 hours', '<strong>Timing:</strong> 1-minute entry, 20-minute run', '<strong>Fee:</strong> free'],
                'ds_rewards' => [
                    'Rewards by <strong>rank (1-4)</strong>: the higher your rank, the more experience and zen.',
                    '<strong>Experience</strong> and <strong>zen</strong> (rank 1 gets the most, zen up to ~150,000 at high levels).',
                ],
                'cc_h' => 'Chaos Castle',
                'cc_goal' => '<strong>Goal (PvP survival):</strong> the <strong>last one standing</strong> wins.',
                'cc_howto' => [
                    'A <strong>PvP survival</strong> event with <strong>no party</strong>: everyone is an opponent.',
                    'Kill monsters to open areas (through stages) while <strong>killing or dodging</strong> other players.',
                    'The <strong>terrain shrinks</strong> each stage, pushing everyone together; watch out for being knocked off the edge.',
                    'The <strong>last survivor</strong> (or highest score) wins.',
                ],
                'cc_facts' => ['<strong>Ticket:</strong> Armor of Guardsman', '<strong>Schedule:</strong> every hour', '<strong>Timing:</strong> 5-minute entry, 10-minute run', '<strong>Entry fee:</strong> scales with level, from 25,000 zen (level 1) to 650,000 zen (level 6)'],
                'cc_rewards' => [
                    '<strong>Various jewels</strong> for the winner (90%): Jewel of Chaos, Bless, Soul, Creation and Life.',
                    'A chance at <strong>ancient items</strong> at higher event levels.',
                ],
            ];

            $section = function (string $h, string $goal, array $howto, array $facts, array $rewards, array $tiers) use ($x, $tierTable) {
                return "<h2>{$h}</h2><p>{$goal}</p>"
                    . "<h3>{$x['l_howto']}</h3>" . $this->olHtml($howto)
                    . "<h3>{$x['l_info']}</h3>" . $this->ulHtml($facts)
                    . "<h3>{$x['l_rewards']}</h3>" . $this->ulHtml($rewards)
                    . $tierTable($tiers, $x['lvl_hdr'], $x['range_hdr']);
            };

            return "<p>{$x['lead']}</p>"
                . "<h2>{$x['h_common']}</h2>" . $this->ulHtml($x['common'])
                . $section($x['bc_h'], $x['bc_goal'], $x['bc_howto'], $x['bc_facts'], $x['bc_rewards'], $bloodTiers)
                . $section($x['ds_h'], $x['ds_goal'], $x['ds_howto'], $x['ds_facts'], $x['ds_rewards'], $devilTiers)
                . $section($x['cc_h'], $x['cc_goal'], $x['cc_howto'], $x['cc_facts'], $x['cc_rewards'], $chaosTiers);
        };

        return [
            'slug' => 'huong-dan-su-kien', 'category' => 'event', 'class_key' => null, 'icon' => 'calendar-star',
            'title_vi' => 'Hướng dẫn sự kiện: Blood Castle, Devil Square, Chaos Castle',
            'title_en' => 'Events guide: Blood Castle, Devil Square, Chaos Castle',
            'excerpt_vi' => 'Cách vào, mục tiêu, vé, khung cấp và phần thưởng của 3 sự kiện vào-cửa.',
            'excerpt_en' => 'How to enter, goals, tickets, level tiers and rewards of the 3 entry-based events.',
            'body_vi' => $build('vi'), 'body_en' => $build('en'), 'sort_order' => 1, 'is_published' => true,
        ];
    }

    // ----------------------------------------------------------------- Gear --

    /** [number, name, dropLevel, chestDefense, strReq, agiReq, levelReq, classes] */
    private function setData(): array
    {
        return [
            [2, 'Pad', 10, 7, 30, 0, 0, 'Dark Wizard, Magic Gladiator'],
            [5, 'Leather', 10, 10, 80, 0, 0, 'Dark Knight, Dark Lord, Magic Gladiator, Rage Fighter'],
            [10, 'Vine', 10, 8, 30, 60, 0, 'Fairy Elf'],
            [0, 'Bronze', 18, 14, 80, 20, 0, 'Dark Knight, Dark Lord, Magic Gladiator'],
            [11, 'Silk', 20, 12, 30, 70, 0, 'Fairy Elf'],
            [4, 'Bone', 22, 13, 40, 0, 0, 'Dark Wizard, Magic Gladiator'],
            [6, 'Scale', 28, 18, 110, 0, 0, 'Dark Knight, Dark Lord, Magic Gladiator, Rage Fighter'],
            [12, 'Wind', 32, 16, 30, 80, 0, 'Fairy Elf'],
            [39, 'Mystery', 34, 22, 39, 0, 0, 'Summoner'],
            [7, 'Sphinx', 38, 17, 40, 0, 0, 'Dark Wizard, Magic Gladiator'],
            [8, 'Brass', 38, 22, 100, 30, 0, 'Dark Knight, Magic Gladiator, Rage Fighter'],
            [13, 'Spirit', 44, 21, 40, 80, 0, 'Fairy Elf'],
            [9, 'Plate', 48, 30, 130, 0, 0, 'Dark Knight, Magic Gladiator, Rage Fighter'],
            [3, 'Legendary', 56, 22, 40, 0, 0, 'Dark Wizard, Magic Gladiator'],
            [40, 'Red Wing', 56, 28, 35, 8, 0, 'Summoner'],
            [14, 'Guardian', 57, 29, 40, 80, 0, 'Fairy Elf'],
            [1, 'Dragon', 59, 37, 120, 30, 0, 'Dark Knight, Magic Gladiator'],
            [25, 'Light Plate', 62, 25, 70, 20, 0, 'Dark Lord'],
            [59, 'Sacred', 66, 43, 85, 0, 1, 'Rage Fighter'],
            [34, 'Ashcrow', 75, 42, 160, 50, 0, 'Dark Knight'],
            [35, 'Eclipse', 75, 27, 53, 12, 0, 'Dark Wizard'],
            [36, 'Iris', 75, 36, 50, 70, 0, 'Fairy Elf'],
            [41, 'Ancient', 75, 35, 52, 16, 0, 'Summoner'],
            [26, 'Adamantine', 78, 36, 77, 21, 0, 'Dark Lord'],
            [15, 'Storm Crow', 80, 44, 150, 70, 0, 'Magic Gladiator'],
            [60, 'Storm Hard', 82, 51, 100, 0, 1, 'Rage Fighter'],
            [16, 'Black Dragon', 90, 48, 170, 60, 0, 'Dark Knight'],
            [18, 'Grand Soul', 91, 33, 59, 20, 0, 'Dark Wizard'],
            [42, 'Black Rose', 91, 45, 60, 20, 0, 'Summoner'],
            [19, 'Divine', 92, 44, 50, 110, 0, 'Fairy Elf'],
            [27, 'Dark Steel', 96, 43, 84, 22, 0, 'Dark Lord'],
            [17, 'Dark Phoenix', 100, 63, 214, 65, 0, 'Dark Knight'],
            [61, 'Piercing', 101, 59, 115, 0, 1, 'Rage Fighter'],
            [37, 'Valiant', 105, 52, 155, 50, 0, 'Magic Gladiator'],
            [38, 'Glorious', 105, 47, 80, 21, 0, 'Dark Lord'],
            [20, 'Thunder Hawk', 107, 60, 170, 70, 0, 'Magic Gladiator'],
            [24, 'Red Spirit', 109, 55, 52, 115, 0, 'Fairy Elf'],
            [44, 'Lilium', 113, 71, 110, 50, 0, 'Summoner'],
            [28, 'Dark Master', 117, 51, 80, 21, 0, 'Dark Lord'],
            [22, 'Dark Soul', 122, 43, 55, 18, 0, 'Dark Wizard'],
            [43, 'Aura', 122, 56, 57, 19, 380, 'Summoner'],
            [50, 'Faith', 122, 52, 32, 29, 0, 'Fairy Elf'],
            [48, 'Phantom', 125, 66, 62, 19, 0, 'Magic Gladiator'],
            [21, 'Great Dragon', 126, 75, 200, 58, 0, 'Dark Knight'],
            [23, 'Hurricane', 128, 73, 162, 66, 0, 'Magic Gladiator'],
            [46, 'Brave', 128, 62, 74, 162, 0, 'Dark Knight'],
            [49, 'Seraphim', 129, 60, 55, 197, 0, 'Fairy Elf'],
            [52, 'Hades', 129, 50, 60, 15, 0, 'Dark Wizard'],
            [47, 'Destroy', 131, 80, 212, 57, 0, 'Magic Gladiator'],
            [45, 'Titan', 132, 81, 222, 32, 0, 'Dark Knight'],
            [51, 'Paewang', 132, 58, 105, 38, 0, 'Dark Lord'],
            [29, 'Dragon Knight', 140, 88, 170, 60, 380, 'Dark Knight'],
            [73, 'Phoenix Soul', 143, 78, 97, 0, 380, 'Rage Fighter'],
            [30, 'Venom Mist', 146, 57, 44, 15, 380, 'Dark Wizard'],
            [31, 'Sylphid Ray', 146, 68, 38, 80, 380, 'Fairy Elf'],
            [32, 'Volcano', 147, 86, 145, 60, 380, 'Magic Gladiator'],
            [33, 'Sunlight', 147, 64, 62, 16, 380, 'Dark Lord'],
        ];
    }

    private function setDetails(): array
    {
        $build = function (string $loc) {
            $rows = '';
            foreach ($this->setData() as $s) {
                $rows .= $this->setRow($s, $loc);
            }
            $filter = $this->filterBar('tbl-sets', $loc);
            $cols = $this->t($loc)['setcols'];
            $th = "<thead><tr><th>{$cols[0]}</th><th>{$cols[1]}</th><th>{$cols[2]}</th><th>{$cols[3]}</th><th>{$cols[4]}</th></tr></thead>";

            $x = $loc === 'vi' ? [
                'lead' => 'Set đồ (bộ giáp) gồm 5 món: <strong>Mũ, Áo, Quần, Găng, Giày</strong>. Mặc đủ bộ kích hoạt <strong>set bonus</strong> (cộng thêm chỉ số). Chọn bộ hợp class và đủ chỉ số yêu cầu để mặc.',
                'h1' => 'Đồ thường, Excellent và Đồ thần',
                'n1' => 'Cùng một bộ đồ có 3 "hạng": <strong>Thường</strong> (chỉ phòng thủ), <strong>Excellent</strong> (thêm dòng option xịn), và <strong>Đồ thần / Ancient</strong> (thêm chỉ số cổ + set bonus). Cả ba <strong>dùng chung một ảnh/model</strong>, chỉ khác hào quang và option.',
                'h2' => 'Cách kiếm (farm) set đồ',
                'p2' => 'Giáp <strong>rơi ngẫu nhiên từ quái</strong>: quái có cấp bằng hoặc cao hơn "cấp độ rơi" của bộ mới rơi ra bộ đó. Không có map riêng cho từng bộ, chỉ cần chọn map có quái cấp phù hợp.',
                'h3' => 'Bảng đầy đủ các bộ giáp',
                'p3' => 'Bấm chip class để lọc (chọn nhiều được). Bộ nào thiếu 1 món (vd Magic Gladiator không đội Mũ) là do class đó không dùng món ấy.',
            ] : [
                'lead' => 'An armor set has 5 pieces: <strong>Helm, Armor, Pants, Gloves, Boots</strong>. Wearing the full set triggers a <strong>set bonus</strong> (extra stats). Pick a set that fits your class and meets its requirements.',
                'h1' => 'Normal, Excellent and Ancient (đồ thần)',
                'n1' => 'A set exists in 3 "grades": <strong>Normal</strong> (defense only), <strong>Excellent</strong> (adds premium options), and <strong>Ancient / đồ thần</strong> (adds ancient stats + set bonus). All three <strong>share the same art/model</strong>, only the glow and options differ.',
                'h2' => 'How to farm armor sets',
                'p2' => 'Armor <strong>drops randomly from monsters</strong>: a monster at or above the set\'s "drop level" can drop it. There is no dedicated map per set, just pick a map with the right monster level.',
                'h3' => 'Full armor set table',
                'p3' => 'Tap class chips to filter (multi-select). If a set is missing a piece (e.g. Magic Gladiator has no helm) it is because that class does not use it.',
            ];

            return "<p>{$x['lead']}</p><h2>{$x['h1']}</h2><div class=\"guide-note\">{$x['n1']}</div>"
                . "<h2>{$x['h2']}</h2><p>{$x['p2']}</p><h2>{$x['h3']}</h2><p>{$x['p3']}</p>{$filter}"
                . '<div class="table-responsive"><table class="mu-itemtable" id="tbl-sets">' . $th . "<tbody>{$rows}</tbody></table></div>";
        };

        return [
            'slug' => 'set-do-thuoc-tinh', 'category' => 'gear', 'class_key' => null, 'icon' => 'shirt',
            'title_vi' => 'Set đồ & thuộc tính (kèm ảnh từng món)', 'title_en' => 'Armor sets & attributes',
            'excerpt_vi' => 'Toàn bộ set giáp: ảnh 5 món, class, phòng thủ và chỉ số yêu cầu.',
            'excerpt_en' => 'All armor sets: 5-piece art, class fit, defense and requirements.',
            'body_vi' => $build('vi'), 'body_en' => $build('en'), 'sort_order' => 1, 'is_published' => true,
        ];
    }

    /** [group, [ [number,name,drop,minDmg,maxDmg,staffRise,str,agi,lvl,classes], ... ] ] */
    private function weaponData(): array
    {
        return [
            [0, [
                [1, 'Short Sword', 3, 3, 7, 0, 60, 0, 0, 'Dark Knight, Dark Lord, Dark Wizard, Fairy Elf, Magic Gladiator, Rage Fighter, Summoner'],
                [0, 'Kris', 6, 6, 11, 0, 40, 40, 0, 'Dark Knight, Dark Lord, Dark Wizard, Fairy Elf, Magic Gladiator, Rage Fighter, Summoner'],
                [2, 'Rapier', 9, 9, 15, 0, 50, 40, 0, 'Dark Knight, Dark Lord, Fairy Elf, Magic Gladiator, Summoner'],
                [4, 'Sword of Assassin', 12, 12, 18, 0, 60, 40, 0, 'Dark Knight, Dark Lord, Magic Gladiator'],
                [3, 'Katache', 16, 16, 26, 0, 80, 40, 0, 'Dark Knight, Dark Lord, Magic Gladiator'],
                [6, 'Gladius', 20, 20, 30, 0, 110, 0, 0, 'Dark Knight, Dark Lord, Fairy Elf, Magic Gladiator'],
                [7, 'Falchion', 24, 24, 34, 0, 120, 0, 0, 'Dark Knight, Dark Lord, Magic Gladiator'],
                [8, 'Serpent Sword', 30, 30, 40, 0, 130, 0, 0, 'Dark Knight, Dark Lord, Magic Gladiator'],
                [9, 'Sword of Salamander', 32, 32, 46, 0, 103, 0, 0, 'Dark Knight, Magic Gladiator'],
                [5, 'Blade', 36, 36, 47, 0, 80, 50, 0, 'Dark Knight, Dark Lord, Dark Wizard, Fairy Elf, Magic Gladiator'],
                [10, 'Light Saber', 40, 47, 61, 0, 80, 60, 0, 'Dark Knight, Fairy Elf, Magic Gladiator'],
                [11, 'Legendary Sword', 44, 56, 72, 0, 120, 0, 0, 'Dark Knight, Magic Gladiator'],
                [13, 'Double Blade', 48, 48, 56, 0, 70, 70, 0, 'Dark Knight, Fairy Elf, Magic Gladiator'],
                [15, 'Giant Sword', 52, 60, 85, 0, 140, 0, 0, 'Dark Knight, Magic Gladiator'],
                [32, 'Sacred Glove', 52, 52, 58, 0, 85, 35, 0, 'Rage Fighter'],
                [12, 'Heliacal Sword', 56, 73, 98, 0, 140, 0, 0, 'Dark Knight, Magic Gladiator'],
                [14, 'Lighting Sword', 59, 59, 67, 0, 90, 50, 0, 'Dark Knight, Fairy Elf, Magic Gladiator'],
                [16, 'Sword of Destruction', 82, 82, 90, 0, 160, 60, 0, 'Dark Knight, Magic Gladiator'],
                [33, 'Storm Hard Glove', 82, 82, 88, 0, 100, 50, 0, 'Rage Fighter'],
                [19, 'Divine Sword of Archangel', 86, 220, 230, 0, 140, 50, 0, 'Dark Knight, Dark Lord, Magic Gladiator'],
                [31, 'Rune Blade', 100, 104, 130, 52, 135, 62, 0, 'Magic Gladiator'],
                [17, 'Dark Breaker', 104, 128, 153, 0, 180, 50, 0, 'Dark Knight'],
                [18, 'Thunder Blade', 105, 140, 168, 0, 180, 50, 0, 'Magic Gladiator'],
                [34, 'Piercing Blade Glove', 105, 95, 101, 0, 120, 60, 0, 'Rage Fighter'],
                [24, 'Daybreak', 115, 182, 218, 0, 192, 30, 0, 'Dark Knight'],
                [25, 'Sword Dancer', 115, 109, 136, 54, 136, 57, 0, 'Magic Gladiator'],
                [27, 'Sword Breaker', 133, 91, 99, 0, 53, 176, 380, 'Dark Knight'],
                [26, 'Flamberge', 137, 115, 126, 0, 193, 53, 380, 'Dark Knight'],
                [28, 'Imperial Sword', 139, 98, 122, 54, 91, 73, 380, 'Magic Gladiator'],
                [20, 'Knight Blade', 140, 107, 115, 0, 116, 38, 0, 'Dark Knight'],
                [21, 'Dark Reign Blade', 140, 115, 142, 58, 116, 53, 0, 'Magic Gladiator'],
                [22, 'Bone Blade', 147, 122, 135, 0, 100, 35, 380, 'Dark Knight'],
                [23, 'Explosion Blade', 147, 127, 155, 67, 98, 48, 380, 'Magic Gladiator'],
                [35, 'Phoenix Soul Star', 147, 122, 128, 0, 101, 51, 380, 'Rage Fighter'],
            ]],
            [1, [
                [0, 'Small Axe', 1, 1, 6, 0, 50, 0, 0, 'Dark Knight, Dark Lord, Dark Wizard, Fairy Elf, Magic Gladiator, Rage Fighter, Summoner'],
                [1, 'Hand Axe', 4, 4, 9, 0, 70, 0, 0, 'Dark Knight, Dark Lord, Dark Wizard, Fairy Elf, Magic Gladiator, Rage Fighter, Summoner'],
                [2, 'Double Axe', 14, 14, 24, 0, 90, 0, 0, 'Dark Knight, Dark Lord, Magic Gladiator'],
                [3, 'Tomahawk', 18, 18, 28, 0, 100, 0, 0, 'Dark Knight, Dark Lord, Magic Gladiator, Rage Fighter'],
                [4, 'Elven Axe', 26, 26, 38, 0, 50, 70, 0, 'Dark Wizard, Fairy Elf, Magic Gladiator, Summoner'],
                [5, 'Battle Axe', 30, 36, 44, 0, 120, 0, 0, 'Dark Knight, Fairy Elf, Magic Gladiator'],
                [6, 'Nikkea Axe', 34, 38, 50, 0, 130, 0, 0, 'Dark Knight, Fairy Elf, Magic Gladiator'],
                [7, 'Larkan Axe', 46, 54, 67, 0, 140, 0, 0, 'Dark Knight, Magic Gladiator'],
                [8, 'Crescent Axe', 54, 69, 89, 0, 100, 40, 0, 'Dark Knight, Dark Wizard, Magic Gladiator'],
            ]],
            [2, [
                [0, 'Mace', 7, 7, 13, 0, 100, 0, 0, 'Dark Knight, Dark Lord, Magic Gladiator, Rage Fighter'],
                [1, 'Morning Star', 13, 13, 22, 0, 100, 0, 0, 'Dark Knight, Dark Lord, Magic Gladiator, Rage Fighter'],
                [2, 'Flail', 22, 22, 32, 0, 80, 50, 0, 'Dark Knight, Dark Lord, Magic Gladiator, Rage Fighter'],
                [3, 'Great Hammer', 38, 45, 56, 0, 150, 0, 0, 'Dark Knight, Magic Gladiator, Rage Fighter'],
                [8, 'Battle Scepter', 54, 41, 52, 0, 80, 17, 0, 'Dark Lord'],
                [4, 'Crystal Morning Star', 66, 78, 107, 0, 130, 0, 0, 'Dark Knight, Dark Wizard, Fairy Elf, Magic Gladiator, Rage Fighter'],
                [5, 'Crystal Sword', 72, 89, 120, 0, 130, 70, 0, 'Dark Knight, Dark Wizard, Fairy Elf, Magic Gladiator'],
                [9, 'Master Scepter', 72, 57, 68, 0, 87, 18, 0, 'Dark Lord'],
                [6, 'Chaos Dragon Axe', 75, 102, 130, 0, 140, 50, 0, 'Dark Knight, Magic Gladiator'],
                [10, 'Great Scepter', 82, 74, 85, 0, 100, 21, 0, 'Dark Lord'],
                [7, 'Elemental Mace', 90, 62, 80, 0, 15, 42, 0, 'Fairy Elf'],
                [11, 'Lord Scepter', 98, 91, 102, 0, 105, 23, 0, 'Dark Lord'],
                [15, 'Shining Scepter', 110, 99, 111, 0, 108, 22, 0, 'Dark Lord'],
                [16, 'Frost Mace', 121, 106, 146, 0, 27, 19, 0, 'Fairy Elf'],
                [17, 'Absolute Scepter', 135, 114, 132, 0, 119, 24, 0, 'Dark Lord'],
                [12, 'Great Lord Scepter', 140, 108, 120, 0, 90, 20, 0, 'Dark Lord'],
                [14, 'Soleil Scepter', 146, 130, 153, 0, 80, 15, 380, 'Dark Lord'],
                [18, 'Stryker Scepter', 147, 112, 124, 0, 87, 20, 0, 'Dark Lord'],
                [13, 'Divine Scepter of Archangel', 150, 200, 223, 0, 75, 16, 0, 'Dark Lord'],
            ]],
            [3, [
                [5, 'Double Poleaxe', 13, 19, 31, 0, 70, 50, 0, 'Dark Knight, Fairy Elf, Magic Gladiator'],
                [2, 'Dragon Lance', 15, 21, 33, 0, 70, 50, 0, 'Dark Knight, Fairy Elf, Magic Gladiator'],
                [6, 'Halberd', 19, 25, 35, 0, 70, 50, 0, 'Dark Knight, Fairy Elf, Magic Gladiator'],
                [1, 'Spear', 23, 30, 41, 0, 70, 50, 0, 'Dark Knight, Fairy Elf, Magic Gladiator'],
                [3, 'Giant Trident', 29, 35, 43, 0, 90, 30, 0, 'Dark Knight, Fairy Elf, Magic Gladiator'],
                [7, 'Berdysh', 37, 42, 54, 0, 80, 50, 0, 'Dark Knight, Fairy Elf, Magic Gladiator'],
                [0, 'Light Spear', 42, 50, 63, 0, 60, 70, 0, 'Dark Knight, Fairy Elf, Magic Gladiator'],
                [4, 'Serpent Spear', 46, 58, 80, 0, 90, 30, 0, 'Dark Knight, Fairy Elf, Magic Gladiator'],
                [8, 'Great Scythe', 54, 71, 92, 0, 90, 50, 0, 'Dark Knight, Fairy Elf, Magic Gladiator'],
                [9, 'Bill of Balrog', 63, 76, 102, 0, 80, 50, 0, 'Dark Knight, Fairy Elf, Magic Gladiator'],
                [10, 'Dragon Spear', 92, 112, 140, 0, 170, 60, 0, 'Dark Knight'],
                [11, 'Beuroba', 147, 190, 226, 0, 152, 25, 0, 'Dark Knight, Magic Gladiator'],
            ]],
            [4, [
                [0, 'Short Bow', 2, 3, 5, 0, 20, 80, 0, 'Fairy Elf'],
                [8, 'Crossbow', 4, 5, 8, 0, 20, 90, 0, 'Fairy Elf'],
                [1, 'Bow', 8, 9, 13, 0, 30, 90, 0, 'Fairy Elf'],
                [9, 'Golden Crossbow', 12, 13, 19, 0, 30, 90, 0, 'Fairy Elf'],
                [2, 'Elven Bow', 16, 17, 24, 0, 30, 90, 0, 'Fairy Elf'],
                [10, 'Arquebus', 20, 22, 30, 0, 30, 90, 0, 'Fairy Elf'],
                [3, 'Battle Bow', 26, 28, 37, 0, 30, 90, 0, 'Fairy Elf'],
                [11, 'Light Crossbow', 32, 35, 44, 0, 30, 90, 0, 'Fairy Elf'],
                [4, 'Tiger Bow', 40, 42, 52, 0, 30, 100, 0, 'Fairy Elf'],
                [12, 'Serpent Crossbow', 48, 50, 61, 0, 30, 100, 0, 'Fairy Elf'],
                [5, 'Silver Bow', 56, 59, 71, 0, 30, 100, 0, 'Fairy Elf'],
                [13, 'Bluewing Crossbow', 68, 68, 82, 0, 40, 110, 0, 'Fairy Elf'],
                [14, 'Aquagold Crossbow', 72, 78, 92, 0, 50, 130, 0, 'Fairy Elf'],
                [6, 'Chaos Nature Bow', 75, 88, 106, 0, 40, 150, 0, 'Fairy Elf'],
                [16, 'Saint Crossbow', 84, 102, 127, 0, 50, 160, 0, 'Fairy Elf'],
                [17, 'Celestial Bow', 92, 127, 155, 0, 54, 198, 0, 'Fairy Elf'],
                [18, 'Divine Crossbow of Archangel', 100, 224, 246, 0, 40, 110, 0, 'Fairy Elf'],
                [19, 'Great Reign Crossbow', 100, 150, 172, 0, 61, 285, 0, 'Fairy Elf'],
                [22, 'Albatross Bow', 110, 155, 177, 0, 60, 265, 0, 'Fairy Elf'],
                [23, 'Stinger Bow', 134, 162, 184, 0, 32, 209, 0, 'Fairy Elf'],
                [20, 'Arrow Viper Bow', 135, 166, 190, 0, 52, 245, 0, 'Fairy Elf'],
                [21, 'Sylph Wind Bow', 147, 177, 200, 0, 46, 210, 380, 'Fairy Elf'],
                [24, 'Air Lyn Bow', 147, 170, 194, 0, 49, 226, 0, 'Fairy Elf'],
            ]],
            [5, [
                [0, 'Skull Staff', 6, 3, 4, 3, 40, 0, 0, 'Dark Wizard, Magic Gladiator, Summoner'],
                [1, 'Angelic Staff', 18, 10, 12, 10, 50, 0, 0, 'Dark Wizard, Magic Gladiator'],
                [14, 'Mistery Stick', 28, 17, 18, 17, 34, 14, 0, 'Summoner'],
                [2, 'Serpent Staff', 30, 17, 18, 17, 50, 0, 0, 'Dark Wizard, Magic Gladiator'],
                [3, 'Thunder Staff', 42, 23, 25, 23, 40, 10, 0, 'Dark Wizard, Magic Gladiator'],
                [15, 'Violent Wind Stick', 42, 23, 25, 23, 33, 17, 0, 'Summoner'],
                [4, 'Gorgon Staff', 52, 29, 32, 29, 50, 0, 0, 'Dark Wizard, Magic Gladiator'],
                [21, 'Book of Sahamutt', 52, 0, 0, 0, 0, 20, 0, 'Summoner'],
                [5, 'Legendary Staff', 59, 29, 31, 30, 50, 0, 0, 'Dark Wizard, Magic Gladiator'],
                [16, 'Red Wing Stick', 59, 29, 31, 30, 36, 14, 0, 'Summoner'],
                [22, 'Book of Neil', 59, 0, 0, 0, 0, 25, 0, 'Summoner'],
                [23, 'Book of Lagle', 65, 0, 0, 0, 0, 30, 0, 'Summoner'],
                [6, 'Staff of Resurrection', 70, 35, 39, 35, 60, 10, 0, 'Dark Wizard, Magic Gladiator'],
                [7, 'Chaos Lightning Staff', 75, 47, 48, 47, 60, 10, 0, 'Dark Wizard, Magic Gladiator'],
                [17, 'Ancient Stick', 78, 38, 40, 38, 50, 19, 0, 'Summoner'],
                [8, 'Staff of Destruction', 90, 50, 54, 50, 60, 10, 0, 'Dark Wizard, Magic Gladiator'],
                [9, 'Dragon Soul Staff', 100, 46, 48, 46, 52, 16, 0, 'Dark Wizard'],
                [18, 'Demonic Stick', 100, 46, 48, 46, 54, 15, 0, 'Summoner'],
                [10, 'Divine Staff of Archangel', 104, 153, 165, 78, 36, 4, 0, 'Dark Wizard, Magic Gladiator'],
                [36, 'Divine Stick of Archangel', 104, 153, 165, 73, 55, 13, 0, 'Summoner'],
                [13, 'Platina Staff', 110, 51, 53, 60, 50, 16, 0, 'Dark Wizard'],
                [19, 'Storm Blitz Stick', 110, 51, 53, 55, 64, 15, 380, 'Summoner'],
                [31, 'Imperial Staff', 137, 57, 61, 62, 48, 14, 380, 'Dark Wizard'],
                [30, 'Deadly Staff', 138, 57, 59, 63, 47, 18, 380, 'Magic Gladiator'],
                [11, 'Staff of Kundun', 140, 55, 61, 55, 45, 16, 0, 'Dark Wizard, Magic Gladiator'],
                [12, 'Grand Viper Staff', 147, 66, 74, 65, 39, 13, 380, 'Dark Wizard'],
                [20, 'Eternal Wing Stick', 147, 66, 74, 53, 57, 13, 380, 'Summoner'],
                [33, 'Chromatic Staff', 147, 55, 57, 62, 50, 12, 0, 'Dark Wizard, Magic Gladiator'],
                [34, 'Raven Stick', 147, 70, 78, 65, 50, 14, 0, 'Summoner'],
            ]],
        ];
    }

    private function weaponsPage(): array
    {
        $build = function (string $loc) {
            $rows = '';
            foreach ($this->weaponData() as [$g, $list]) {
                foreach ($list as $w) {
                    $rows .= $this->weaponRow($g, $w, $loc);
                }
            }
            $filter = $this->filterBar('tbl-weapons', $loc);
            $cols = $this->t($loc)['wpncols'];
            $th = "<thead><tr><th>{$cols[0]}</th><th>{$cols[1]}</th><th>{$cols[2]}</th><th>{$cols[3]}</th><th>{$cols[4]}</th></tr></thead>";

            $x = $loc === 'vi' ? [
                'lead' => 'Vũ khí quyết định sát thương chính. Mỗi class dùng loại riêng: Dark Knight và Magic Gladiator dùng Kiếm / Rìu / Chuỳ / Giáo; Fairy Elf dùng Cung / Nỏ; Dark Wizard dùng Gậy phép; Dark Lord dùng Gậy quyền (Scepter); Summoner dùng Gậy và Sách; Rage Fighter dùng Găng đấm.',
                'note' => '"Sát thương" là sát thương gốc của vũ khí (chưa cộng chỉ số, Excellent hay nâng cấp +). Gậy phép ghi thêm "Sức mạnh phép" là % tăng sát thương phép. Vũ khí rơi từ quái theo cấp giống giáp.',
                'h' => 'Bảng đầy đủ vũ khí', 'p' => 'Bấm chip class để lọc (chọn nhiều được).',
            ] : [
                'lead' => 'Your weapon drives your main damage. Each class uses its own type: Dark Knight and Magic Gladiator use Sword / Axe / Mace / Spear; Fairy Elf uses Bow / Crossbow; Dark Wizard uses staffs; Dark Lord uses Scepters; Summoner uses sticks and books; Rage Fighter uses gloves.',
                'note' => '"Damage" is the weapon\'s base damage (before stats, Excellent options or + upgrades). Staffs also show "Magic power" which is the % magic-damage boost. Weapons drop from monsters by level like armor.',
                'h' => 'Full weapon table', 'p' => 'Tap class chips to filter (multi-select).',
            ];

            return "<p>{$x['lead']}</p><div class=\"guide-note\">{$x['note']}</div><h2>{$x['h']}</h2><p>{$x['p']}</p>{$filter}"
                . '<div class="table-responsive"><table class="mu-itemtable" id="tbl-weapons">' . $th . "<tbody>{$rows}</tbody></table></div>";
        };

        return [
            'slug' => 'vu-khi-thuoc-tinh', 'category' => 'gear', 'class_key' => null, 'icon' => 'khanda',
            'title_vi' => 'Vũ khí & thuộc tính (kèm ảnh)', 'title_en' => 'Weapons & attributes',
            'excerpt_vi' => 'Toàn bộ vũ khí: ảnh, sát thương, chỉ số yêu cầu, class và nơi farm.',
            'excerpt_en' => 'All weapons: art, damage, requirements, class and farm map.',
            'body_vi' => $build('vi'), 'body_en' => $build('en'), 'sort_order' => 2, 'is_published' => true,
        ];
    }

    // -------------------------------------------------------------- Classes --

    private function classBody(array $d, string $loc): string
    {
        $vi = $loc === 'vi';
        $t = $vi
            ? ['skills' => 'Kỹ năng chính', 'sets' => 'Set đồ theo cấp độ', 'setsp' => 'Các bộ đồ ' . $d['name'] . ' có thể mặc, xếp theo cấp độ rơi (lấy từ cấu hình muss6):', 'tier' => 'Giai đoạn', 'setcol' => 'Bộ đồ (cấp độ rơi)',
                'grades' => '<li><strong>Đồ thường</strong>: chỉ phòng thủ cơ bản.</li><li><strong>Đồ Excellent</strong>: có dòng option xịn (hồi HP/MP, tăng % sát thương...). Rơi từ Blood Castle, boss.</li><li><strong>Đồ thần (Ancient)</strong>: có chỉ số cổ + set bonus.</li>',
                'wpn' => 'Vũ khí phù hợp', 'wings' => 'Wings cho ' . $d['name'], 'wingp' => 'Cách chế xem bài <strong>Cách xoay Wings tại Chaos Machine</strong>.', 'build' => 'Cộng điểm cho ' . $d['name'],
                'play' => 'Lối chơi & lên cấp', 'pvp' => 'PvP & vai trò trong party',
                'note' => 'Build theo kinh nghiệm Season 6. GM có thể tinh chỉnh.']
            : ['skills' => 'Key skills', 'sets' => 'Armor sets by level', 'setsp' => 'Sets ' . $d['name'] . ' can wear, sorted by drop level (from the muss6 config):', 'tier' => 'Stage', 'setcol' => 'Set (drop level)',
                'grades' => '<li><strong>Normal</strong>: base defense only.</li><li><strong>Excellent</strong>: premium options (HP/MP recovery, % damage...). Drops from Blood Castle, bosses.</li><li><strong>Ancient (đồ thần)</strong>: ancient stats + set bonus.</li>',
                'wpn' => 'Suitable weapons', 'wings' => 'Wings for ' . $d['name'], 'wingp' => 'See <strong>How to craft wings at the Chaos Machine</strong>.', 'build' => 'Stat build for ' . $d['name'],
                'play' => 'Playstyle & leveling', 'pvp' => 'PvP & party role',
                'note' => 'Build follows Season 6 conventions. GMs may tune it.'];

        $rows = '';
        foreach ($d['tiers'] as $tierKey => $sets) {
            $rows .= '<tr><td>' . $d['tierlabels'][$tierKey][$loc] . '</td><td>' . implode(' → ', $sets) . '</td></tr>';
        }

        $wingCards = '';
        foreach ($d['wings'] as $wg) {
            $wingCards .= '<li>' . $this->img($wg[0], $wg[1]) . ' <strong>' . $wg[1] . '</strong>: ' . $wg[2][$loc] . '</li>';
        }

        $ancient = $vi
            ? '<div class="guide-note">"Đồ thần" (Ancient) là hạng option của món đồ: cùng bộ có bản thường, Excellent và Ancient. Bản Ancient có chỉ số cổ + set bonus. Kiếm từ Kanturu, Land of Trials, hoặc chế qua Chaos Machine.</div>'
            : '<div class="guide-note">"Ancient" (đồ thần) is an option grade: a set has Normal, Excellent and Ancient versions. Ancient adds ancient stats + a set bonus. Found at Kanturu, Land of Trials, or crafted at the Chaos Machine.</div>';

        return "<p>{$d['intro'][$loc]}</p>"
            . "<h2>{$t['skills']}</h2>{$d['skills'][$loc]}"
            . "<h2>{$t['build']}</h2>{$d['build'][$loc]}<div class=\"guide-note\">{$t['note']}</div>"
            . "<h2>{$t['sets']}</h2><p>{$t['setsp']}</p>"
            . '<div class="table-responsive"><table><thead><tr><th>' . $t['tier'] . '</th><th>' . $t['setcol'] . '</th></tr></thead><tbody>' . $rows . '</tbody></table></div>'
            . '<h3>' . ($vi ? 'Đồ thường, Excellent và Đồ thần' : 'Normal, Excellent and Ancient') . "</h3><ul>{$t['grades']}</ul>{$ancient}"
            . "<h2>{$t['wpn']}</h2><p>{$d['weapon'][$loc]}</p>"
            . "<h2>{$t['wings']}</h2><ul class=\"mu-wing-list\">{$wingCards}</ul><p>{$t['wingp']}</p>"
            . "<h2>{$t['play']}</h2>{$d['playstyle'][$loc]}" . $this->levelingLadder($loc)
            . "<h2>{$t['pvp']}</h2>{$d['pvp'][$loc]}";
    }

    /** Grounded map/level progression ladder shared by every class guide (monster ranges from the muss6 map config). */
    private function levelingLadder(string $loc): string
    {
        $vi = $loc === 'vi';
        $rows = $vi ? [
            ['Cấp 1-30', 'Lorencia, Noria', 'Bãi khởi đầu an toàn, quái cấp 2-19. Làm quen phím, kiếm zen và đồ đầu tiên.'],
            ['Cấp 30-60', 'Devias, Dungeon', 'Quái cấp 20-52. Bắt đầu đi <a href="/events">Blood Castle</a> để lấy đồ Excellent.'],
            ['Cấp 60-100', 'Lost Tower, Atlans', 'Quái cấp 43-90. Săn set trung cấp và Jewel để ép đồ.'],
            ['Cấp 100-150', 'Aida, Icarus', 'Quái cấp 72-120. Cày set cao cấp, làm wings cấp 2-3.'],
            ['Cấp 150+', 'Kanturu, boss & sự kiện', 'Quái cấp 80-129, boss thế giới. Farm đồ thần (Ancient) và item chế wings.'],
        ] : [
            ['Level 1-30', 'Lorencia, Noria', 'Safe starter maps, monsters level 2-19. Learn the controls, earn zen and first gear.'],
            ['Level 30-60', 'Devias, Dungeon', 'Monsters level 20-52. Start running <a href="/en/events">Blood Castle</a> for Excellent gear.'],
            ['Level 60-100', 'Lost Tower, Atlans', 'Monsters level 43-90. Farm mid sets and Jewels to upgrade gear.'],
            ['Level 100-150', 'Aida, Icarus', 'Monsters level 72-120. Farm high-end sets, craft level 2-3 wings.'],
            ['Level 150+', 'Kanturu, bosses & events', 'Monsters level 80-129 and world bosses. Farm Ancient sets and wing-crafting items.'],
        ];
        $head = $vi ? ['Cấp nhân vật', 'Bản đồ gợi ý', 'Ghi chú'] : ['Character level', 'Suggested maps', 'Notes'];
        $lead = $vi
            ? '<p>Lộ trình bãi cày theo cấp (mức quái lấy từ cấu hình muss6). Dùng <a href="/huong-dan/luyen-cap-offline">luyện cấp offline (/offlevel)</a> để cày cả khi tắt game, và <a href="/huong-dan/quan-ly-nhan-vat">Reset</a> ở cấp 400 để chơi lại mạnh hơn.</p>'
            : '<p>Farming route by level (monster levels from the muss6 config). Use <a href="/en/huong-dan/luyen-cap-offline">offline leveling (/offlevel)</a> to grind while logged out, and <a href="/en/huong-dan/quan-ly-nhan-vat">Reset</a> at level 400 to replay stronger.</p>';
        $body = '';
        foreach ($rows as $r) {
            $body .= '<tr><td>' . $r[0] . '</td><td>' . $r[1] . '</td><td>' . $r[2] . '</td></tr>';
        }

        return $lead . '<div class="table-responsive"><table><thead><tr><th>' . $head[0] . '</th><th>' . $head[1] . '</th><th>' . $head[2] . '</th></tr></thead><tbody>' . $body . '</tbody></table></div>';
    }

    private function classGuides(): array
    {
        $tl = [
            'starter' => ['vi' => 'Sơ cấp', 'en' => 'Starter'],
            'mid'     => ['vi' => 'Trung cấp', 'en' => 'Mid'],
            'high'    => ['vi' => 'Cao cấp', 'en' => 'High'],
            'top'     => ['vi' => 'Đỉnh cao', 'en' => 'Top'],
        ];
        $lv = fn (int $n) => ['vi' => 'cấp ' . $n, 'en' => 'level ' . $n];

        $defs = [
            [
                'slug' => 'dark-knight', 'class_key' => 'dark-knight', 'icon' => 'khanda', 'order' => 1,
                'title' => 'Dark Knight (Blade Knight)', 'name' => 'Dark Knight',
                'excerpt_vi' => 'Set đồ, wings và cách cộng điểm cho Dark Knight.', 'excerpt_en' => 'Gear, wings and stat build for the Dark Knight.',
                'intro' => ['vi' => 'Dark Knight (chuyển sinh thành <strong>Blade Knight</strong>) là class cận chiến máu trâu, sát thương vật lý cao, dễ chơi, hợp người mới.', 'en' => 'The Dark Knight (evolves to <strong>Blade Knight</strong>) is a tanky melee class with high physical damage, easy to play and great for new players.'],
                'weapon' => ['vi' => 'Kiếm, Rìu, Chuỳ, Giáo (sát thương vật lý). Ưu tiên vũ khí Excellent.', 'en' => 'Sword, Axe, Mace, Spear (physical damage). Prefer Excellent weapons.'],
                'tiers' => ['starter' => ['Leather (10)', 'Bronze (18)', 'Scale (28)'], 'mid' => ['Brass (38)', 'Plate (48)', 'Dragon (59)'], 'high' => ['Ashcrow (75)', 'Black Dragon (90)', 'Dark Phoenix (100)'], 'top' => ['Great Dragon (126)', 'Brave (128)', 'Titan (132)', 'Dragon Knight (140)']],
                'wings' => [['12_2', 'Wings of Satan', $lv(1)], ['12_5', 'Wings of Dragon', $lv(2)], ['12_36', 'Wing of Storm', $lv(3)]],
                'build' => ['vi' => '<ul><li><strong>Strength</strong>: sát thương chính, dồn nhiều nhất.</li><li><strong>Agility</strong>: đủ mặc đồ và tốc đánh.</li><li><strong>Vitality</strong>: tăng khi lên đồ tốt, nhất là PvP.</li></ul>', 'en' => '<ul><li><strong>Strength</strong>: main damage, put the most here.</li><li><strong>Agility</strong>: enough for gear and attack speed.</li><li><strong>Vitality</strong>: add as you get better gear, especially for PvP.</li></ul>'],
                'skills' => ['vi' => '<p>Đòn đánh của Dark Knight đến từ vũ khí và sách kỹ năng (Skill orb). Các kỹ năng đáng chú ý:</p><ul><li><strong>Twisting Slash (Xoáy kiếm)</strong>: học khi cầm kiếm 1 tay, chém xoay quanh người, đòn dọn quái (AoE) chủ lực khi cày.</li><li><strong>Rageful Blow</strong>: kỹ năng diện rộng của Blade Knight, sát thương lớn theo vùng, quét cụm quái rất nhanh.</li><li><strong>Death Stab (Thương xuyên)</strong>: đòn đơn mục tiêu sát thương rất cao, dùng hạ boss và PvP.</li><li><strong>Cyclone, Slash, Uppercut, Lunge</strong>: các đòn kiếm cơ bản dùng từ sớm.</li></ul><p><strong>Buff:</strong> <strong>Swell Life</strong> tăng máu tối đa cho cả nhóm (rất được ưa thích khi đi party); <strong>Defense</strong> tự tăng phòng thủ. Rageful Blow và Death Stab mở khi chuyển sinh thành Blade Knight, học qua Skill orb.</p>', 'en' => '<p>The Dark Knight gets attacks from weapons and skill orbs. Notable skills:</p><ul><li><strong>Twisting Slash</strong>: learned while holding a one-handed sword, spins around you, the main AoE farming skill.</li><li><strong>Rageful Blow</strong>: a Blade Knight area skill, big AoE damage that clears packs fast.</li><li><strong>Death Stab</strong>: very high single-target damage, used to kill bosses and in PvP.</li><li><strong>Cyclone, Slash, Uppercut, Lunge</strong>: basic sword strikes used early on.</li></ul><p><strong>Buffs:</strong> <strong>Swell Life</strong> raises the whole party\'s max HP (very wanted in groups); <strong>Defense</strong> self-buffs your armor. Rageful Blow and Death Stab unlock on evolving to Blade Knight, learned from skill orbs.</p>'],
                'playstyle' => ['vi' => '<p>Dark Knight cày rất khoẻ nhờ máu trâu và đòn AoE (Twisting Slash / Rageful Blow): lao vào giữa cụm quái rồi quét sạch. Vì tự trụ tốt nên cực hợp <a href="/huong-dan/luyen-cap-offline">luyện cấp offline</a> ở bãi đông quái.</p><ul><li><strong>Đầu game:</strong> dồn Strength để giết nhanh, chỉ cộng Agility đủ mặc đồ.</li><li><strong>Giữa game:</strong> ưu tiên kiếm 1 tay + khiên để vừa cày vừa an toàn; ép đồ Excellent lấy từ Blood Castle.</li><li><strong>Cuối game:</strong> đủ đồ rồi thì thêm Vitality để bước vào PvP.</li></ul>', 'en' => '<p>The Dark Knight farms very well thanks to high HP and AoE (Twisting Slash / Rageful Blow): dive into a pack and clear it. Because it survives on its own, it is a great fit for <a href="/en/huong-dan/luyen-cap-offline">offline leveling</a> in dense spots.</p><ul><li><strong>Early:</strong> stack Strength to kill fast, only add Agility to meet gear requirements.</li><li><strong>Mid:</strong> favor a one-handed sword + shield to farm safely; upgrade Excellent gear from Blood Castle.</li><li><strong>Late:</strong> once geared, add Vitality to move into PvP.</li></ul>'],
                'pvp' => ['vi' => '<p>Dark Knight là "cỗ xe tăng" của party: nhiều máu, đứng tuyến đầu chịu đòn và mở combo. PvP tay đôi, Death Stab bộc phát rất mạnh, kết hợp Swell Life để trâu hơn. Điểm yếu là đánh gần, dễ bị pháp sư/cung thủ kéo (kite), nên cần đủ tốc chạy (wings, giày) và Vitality để áp sát.</p><ul><li><strong>Vai trò party:</strong> tuyến đầu, buff máu (Swell Life), kéo và giữ quái.</li><li><strong>PvP:</strong> áp sát nhanh, tung Death Stab; giữ Vitality cao để không bị bốc hơi.</li></ul>', 'en' => '<p>The Dark Knight is the party tank: high HP, holds the front line and opens combos. In 1v1, Death Stab bursts hard, paired with Swell Life for extra bulk. Its weakness is short range, easily kited by mages/archers, so it needs move speed (wings, boots) and Vitality to close in.</p><ul><li><strong>Party role:</strong> front line, HP buff (Swell Life), pulling and holding monsters.</li><li><strong>PvP:</strong> close in fast, land Death Stab; keep Vitality high so you do not get bursted down.</li></ul>'],
            ],
            [
                'slug' => 'dark-wizard', 'class_key' => 'dark-wizard', 'icon' => 'hat-wizard', 'order' => 2,
                'title' => 'Dark Wizard (Soul Master)', 'name' => 'Dark Wizard',
                'excerpt_vi' => 'Set đồ, wings và cách cộng điểm cho Dark Wizard.', 'excerpt_en' => 'Gear, wings and stat build for the Dark Wizard.',
                'intro' => ['vi' => 'Dark Wizard (chuyển sinh thành <strong>Soul Master</strong>) là pháp sư tầm xa, sát thương phép diện rộng, dọn quái nhanh. Máu mỏng nên cần giữ khoảng cách.', 'en' => 'The Dark Wizard (evolves to <strong>Soul Master</strong>) is a ranged mage with strong AoE magic, clearing packs fast. Squishy, so keep your distance.'],
                'weapon' => ['vi' => 'Gậy phép (Staff) và Skill/Orb để học kỹ năng. Ưu tiên Staff Excellent.', 'en' => 'Staffs plus skill orbs to learn spells. Prefer Excellent staffs.'],
                'tiers' => ['starter' => ['Pad (10)', 'Bone (22)', 'Sphinx (38)'], 'mid' => ['Legendary (56)', 'Eclipse (75)'], 'high' => ['Grand Soul (91)', 'Dark Soul (122)'], 'top' => ['Hades (129)', 'Venom Mist (146)']],
                'wings' => [['12_1', 'Wings of Heaven', $lv(1)], ['12_4', 'Wings of Soul', $lv(2)], ['12_37', 'Wing of Eternal', $lv(3)]],
                'build' => ['vi' => '<ul><li><strong>Energy</strong>: sát thương phép, dồn nhiều nhất.</li><li><strong>Vitality</strong>: thêm để sống (máu Wizard rất mỏng).</li><li><strong>Agility/Strength</strong>: đủ mặc đồ.</li></ul>', 'en' => '<ul><li><strong>Energy</strong>: magic damage, put the most here.</li><li><strong>Vitality</strong>: add to survive (very low HP).</li><li><strong>Agility/Strength</strong>: just enough for gear.</li></ul>'],
                'skills' => ['vi' => '<p>Gần như mọi phép của Dark Wizard học từ sách kỹ năng (Skill orb): mua hoặc nhặt orb rồi nhấp để học. Các phép đáng chú ý:</p><ul><li><strong>Evil Spirit (Ác linh)</strong>: đòn diện rộng (AoE) chủ lực, dọn quái cả màn hình, dùng xuyên suốt.</li><li><strong>Energy Ball, Power Wave</strong>: phép cơ bản đầu game khi ít điểm.</li><li><strong>Fire Ball, Lightning, Ice</strong>: phép đơn mục tiêu theo từng hệ.</li><li><strong>Nova</strong>: dồn năng lượng rồi bung, sát thương bùng nổ (mở khi thành Soul Master).</li><li><strong>Meteor, Inferno, Aqua Beam</strong>: các phép AoE sát thương cao về sau.</li></ul><p><strong>Buff:</strong> <strong>Soul Barrier</strong> giảm % sát thương nhận (cực mạnh khi PvP), <strong>Teleport</strong> dịch chuyển né đòn cận chiến (Soul Master).</p>', 'en' => '<p>Almost every Dark Wizard spell is learned from skill orbs: buy or loot an orb and click to learn it. Notable spells:</p><ul><li><strong>Evil Spirit</strong>: the main AoE, clears whole screens of monsters, used the entire game.</li><li><strong>Energy Ball, Power Wave</strong>: basic early spells when points are scarce.</li><li><strong>Fire Ball, Lightning, Ice</strong>: single-target spells by element.</li><li><strong>Nova</strong>: charges energy then bursts for very high damage (unlocks as Soul Master).</li><li><strong>Meteor, Inferno, Aqua Beam</strong>: high-damage AoE spells later on.</li></ul><p><strong>Buffs:</strong> <strong>Soul Barrier</strong> reduces % damage taken (very strong in PvP), <strong>Teleport</strong> blinks away from melee (Soul Master).</p>'],
                'playstyle' => ['vi' => '<p>Dark Wizard dọn quái nhanh nhất game nhờ Evil Spirit diện rộng, nhưng máu rất mỏng nên phải đánh tầm xa, đứng ngoài dội phép vào. Hợp <a href="/huong-dan/luyen-cap-offline">luyện cấp offline</a> ở bãi quái đứng yên, và cực giá trị khi đi party (dọn quái cho cả nhóm).</p><ul><li><strong>Đầu game:</strong> dồn Energy, chỉ cộng Agi/Str đủ mặc đồ.</li><li><strong>Giữa game:</strong> thêm chút Vitality để không bị 1 hit; farm Blood Castle lấy đồ Excellent hồi HP/MP.</li><li><strong>Cuối game:</strong> Soul Barrier + Vitality để đủ sức đứng PvP.</li></ul>', 'en' => '<p>The Dark Wizard clears monsters fastest thanks to AoE Evil Spirit, but is very squishy, so fight at range and cast from outside. Great for <a href="/en/huong-dan/luyen-cap-offline">offline leveling</a> in static spots, and very valuable in a party (clears packs for everyone).</p><ul><li><strong>Early:</strong> stack Energy, add only enough Agi/Str for gear.</li><li><strong>Mid:</strong> add a little Vitality so you are not one-shot; farm Blood Castle for HP/MP-recovery Excellent gear.</li><li><strong>Late:</strong> Soul Barrier + Vitality to hold up in PvP.</li></ul>'],
                'pvp' => ['vi' => '<p>Sát thương phép bùng nổ: Nova và Evil Spirit hạ mục tiêu rất nhanh, còn Soul Barrier giảm damage nhận giúp sống dai bất ngờ, Teleport né đòn áp sát. Điểm yếu lớn nhất là máu mỏng, sợ bị Dark Knight/Rage Fighter áp sát, nên phải giữ khoảng cách và cộng đủ Vitality.</p><ul><li><strong>Vai trò party:</strong> sát thương diện rộng, dọn quái và đông người.</li><li><strong>PvP:</strong> kite giữ khoảng cách, bật Soul Barrier, canh Nova khi địch dính đòn.</li></ul>', 'en' => '<p>Burst magic damage: Nova and Evil Spirit delete targets fast, while Soul Barrier cuts incoming damage for surprising survivability and Teleport dodges gap-closers. The big weakness is low HP, vulnerable to a Dark Knight/Rage Fighter closing in, so keep distance and invest in Vitality.</p><ul><li><strong>Party role:</strong> AoE damage, clearing packs and crowds.</li><li><strong>PvP:</strong> kite at range, keep Soul Barrier up, time Nova when the enemy is caught.</li></ul>'],
            ],
            [
                'slug' => 'fairy-elf', 'class_key' => 'fairy-elf', 'icon' => 'bullseye', 'order' => 3,
                'title' => 'Fairy Elf (Muse Elf)', 'name' => 'Fairy Elf',
                'excerpt_vi' => 'Set đồ, wings và cách cộng điểm cho Fairy Elf.', 'excerpt_en' => 'Gear, wings and stat build for the Fairy Elf.',
                'intro' => ['vi' => 'Fairy Elf (chuyển sinh thành <strong>Muse Elf</strong>) linh hoạt: build cung thủ tầm xa hoặc build hỗ trợ (buff cho team). Rất được săn đón khi đi party.', 'en' => 'The Fairy Elf (evolves to <strong>Muse Elf</strong>) is flexible: build as a ranged archer or as support (buffs the party). Highly wanted in groups.'],
                'weapon' => ['vi' => 'Cung và Nỏ (Crossbow). Build buff thì ưu tiên Energy.', 'en' => 'Bows and Crossbows. For a buff build, prioritize Energy.'],
                'tiers' => ['starter' => ['Vine (10)', 'Silk (20)', 'Wind (32)'], 'mid' => ['Spirit (44)', 'Guardian (57)', 'Iris (75)'], 'high' => ['Divine (92)', 'Red Spirit (109)'], 'top' => ['Faith (122)', 'Seraphim (129)', 'Sylphid Ray (146)']],
                'wings' => [['12_0', 'Wings of Elf', $lv(1)], ['12_3', 'Wings of Spirits', $lv(2)], ['12_38', 'Wing of Illusion', $lv(3)]],
                'build' => ['vi' => '<ul><li><strong>Agility</strong>: sát thương cung + thủ, chỉ số chính.</li><li><strong>Energy</strong>: cốt lõi nếu build hỗ trợ.</li><li><strong>Vitality</strong>: vừa phải.</li></ul>', 'en' => '<ul><li><strong>Agility</strong>: bow damage + defense, the main stat.</li><li><strong>Energy</strong>: core for a support build.</li><li><strong>Vitality</strong>: a moderate amount.</li></ul>'],
                'skills' => ['vi' => '<p>Đòn bắn đến từ cung/nỏ; các buff hỗ trợ học từ sách kỹ năng (Skill orb). Kỹ năng đáng chú ý:</p><ul><li><strong>Triple Shot (Ba mũi tên)</strong>: bắn 3 mũi cùng lúc, đòn cày cơ bản.</li><li><strong>Penetration (Xuyên tâm)</strong>: mũi tên xuyên thẳng một hàng, đòn AoE chủ lực.</li><li><strong>Multi-Shot, Ice Arrow</strong>: mở khi thành Muse Elf, AoE và làm chậm mục tiêu.</li></ul><p><strong>Buff hỗ trợ (build Energy):</strong> <strong>Greater Damage</strong> tăng sát thương cả nhóm, <strong>Greater Defense</strong> tăng thủ cả nhóm, <strong>Heal</strong> hồi máu đồng đội, <strong>Infinity Arrow</strong> bắn không tốn tên. Elf còn có kỹ năng <strong>triệu hồi (Summon)</strong> gọi quái phụ đánh cùng.</p>', 'en' => '<p>Shots come from bows/crossbows; support buffs are learned from skill orbs. Notable skills:</p><ul><li><strong>Triple Shot</strong>: fires 3 arrows at once, the basic farming skill.</li><li><strong>Penetration</strong>: arrows pierce straight through a line, the main AoE.</li><li><strong>Multi-Shot, Ice Arrow</strong>: unlock as Muse Elf, AoE and slow.</li></ul><p><strong>Support buffs (Energy build):</strong> <strong>Greater Damage</strong> boosts the party\'s damage, <strong>Greater Defense</strong> boosts defense, <strong>Heal</strong> heals allies, <strong>Infinity Arrow</strong> removes arrow cost. Elves also have <strong>Summon</strong> skills to call a helper monster.</p>'],
                'playstyle' => ['vi' => '<p>Fairy Elf có 2 hướng rõ rệt. <strong>Build Agility (cung thủ)</strong> bắn xa, sát thương cao, tự cày tốt. <strong>Build Energy (hỗ trợ)</strong> buff công/thủ và heal cho cả team, được săn đón trong mọi party, Blood Castle, Chaos Castle. Cung thủ hợp <a href="/huong-dan/luyen-cap-offline">luyện cấp offline</a>; hỗ trợ nên chơi tay để canh buff/heal.</p><ul><li><strong>Đầu game:</strong> cung thủ dồn Agility; hỗ trợ dồn Energy.</li><li><strong>Giữa game:</strong> cung thủ bắn tầm xa an toàn; hỗ trợ bám sát party.</li><li><strong>Cuối game:</strong> thêm Vitality để trụ, nhất là hỗ trợ (máu mỏng).</li></ul>', 'en' => '<p>The Fairy Elf has two clear builds. <strong>Agility (archer)</strong> shoots far, hits hard, farms well solo. <strong>Energy (support)</strong> buffs damage/defense and heals the team, wanted in every party, Blood Castle and Chaos Castle. The archer suits <a href="/en/huong-dan/luyen-cap-offline">offline leveling</a>; support is best played hands-on to time buffs/heals.</p><ul><li><strong>Early:</strong> archer stacks Agility; support stacks Energy.</li><li><strong>Mid:</strong> archer shoots safely from range; support sticks with the party.</li><li><strong>Late:</strong> add Vitality to survive, especially support (squishy).</li></ul>'],
                'pvp' => ['vi' => '<p>Cung thủ gây sát thương tầm xa và kite rất tốt. Hỗ trợ là "trái tim" của party: Greater Damage giúp cả nhóm tăng damage, Heal giữ mạng đồng đội, giá trị cực cao ở PvP tập thể và Castle Siege. Điểm yếu chung là máu mỏng, sợ bị áp sát.</p><ul><li><strong>Vai trò party:</strong> buff Greater Damage/Defense + Heal (được ưu tiên vào nhóm).</li><li><strong>PvP:</strong> cung thủ kite giữ khoảng cách; hỗ trợ đứng sau buff và heal liên tục.</li></ul>', 'en' => '<p>The archer deals ranged damage and kites well. Support is the heart of a party: Greater Damage raises everyone\'s damage and Heal keeps allies alive, hugely valuable in group PvP and Castle Siege. The shared weakness is low HP, vulnerable to gap-closers.</p><ul><li><strong>Party role:</strong> Greater Damage/Defense buffs + Heal (a priority invite).</li><li><strong>PvP:</strong> archer kites at range; support stays behind, buffing and healing.</li></ul>'],
            ],
            [
                'slug' => 'magic-gladiator', 'class_key' => 'magic-gladiator', 'icon' => 'meteor', 'order' => 4,
                'title' => 'Magic Gladiator', 'name' => 'Magic Gladiator',
                'excerpt_vi' => 'Set đồ, wings và cách cộng điểm cho Magic Gladiator.', 'excerpt_en' => 'Gear, wings and stat build for the Magic Gladiator.',
                'intro' => ['vi' => 'Magic Gladiator là class lai chiến binh và pháp sư, <strong>không cần chuyển sinh</strong>. Nhận nhiều điểm mỗi cấp, mạnh mọi giai đoạn, nhưng không đội được Mũ.', 'en' => 'The Magic Gladiator is a warrior-mage hybrid that <strong>needs no evolution</strong>. It gets more stat points per level and is strong throughout, but cannot wear a Helm.'],
                'weapon' => ['vi' => 'Kiếm, Giáo (vật lý) hoặc Gậy (phép). Chọn theo hướng build.', 'en' => 'Sword, Spear (physical) or Staff (magic), depending on your build.'],
                'tiers' => ['starter' => ['Leather/Pad (10)', 'Bronze (18)', 'Scale (28)'], 'mid' => ['Brass (38)', 'Plate (48)', 'Dragon (59)'], 'high' => ['Storm Crow (80)', 'Valiant (105)', 'Thunder Hawk (107)'], 'top' => ['Hurricane (128)', 'Destroy (131)', 'Volcano (147)']],
                'wings' => [['12_2', 'Wings of Heaven/Satan', $lv(1)], ['12_6', 'Wings of Darkness', $lv(2)], ['12_39', 'Wing of Ruin', $lv(3)]],
                'build' => ['vi' => '<ul><li><strong>Strength</strong>: hướng vật lý (dễ chơi).</li><li><strong>Energy</strong>: hướng phép, hoặc lai Str+Energy.</li><li><strong>Vitality</strong>: thêm để trụ PvP.</li></ul>', 'en' => '<ul><li><strong>Strength</strong>: physical build (easy).</li><li><strong>Energy</strong>: magic build, or a Str+Energy hybrid.</li><li><strong>Vitality</strong>: add for PvP survivability.</li></ul>'],
                'skills' => ['vi' => '<p>Magic Gladiator lai chiến binh và pháp sư, dùng cả đòn kiếm lẫn đòn phép. Chọn kỹ năng theo hướng build:</p><ul><li><strong>Twisting Slash (Xoáy kiếm)</strong>: học từ kiếm 1 tay, chém xoay AoE, đòn cày chủ lực hệ vật lý.</li><li><strong>Fire Slash</strong>: chém ra sóng lửa diện rộng.</li><li><strong>Power Slash</strong>: chém sóng tầm xa.</li><li><strong>Mana Rays, Flame Strike, Gigantic Storm</strong>: đòn phép cho hướng Energy.</li></ul><p><strong>Buff:</strong> <strong>Defense</strong> tự tăng phòng thủ. Lưu ý: hướng Strength dùng đòn kiếm, hướng Energy dùng đòn phép, có thể lai cả hai.</p>', 'en' => '<p>The Magic Gladiator is a warrior-mage hybrid using both sword and spell skills. Pick skills to match your build:</p><ul><li><strong>Twisting Slash</strong>: learned from a one-handed sword, spinning AoE, the main physical farming skill.</li><li><strong>Fire Slash</strong>: sends out a wide arc of fire.</li><li><strong>Power Slash</strong>: a ranged slash wave.</li><li><strong>Mana Rays, Flame Strike, Gigantic Storm</strong>: spell attacks for an Energy build.</li></ul><p><strong>Buff:</strong> <strong>Defense</strong> self-buffs armor. Note: Strength builds use sword skills, Energy builds use spells, and you can mix both.</p>'],
                'playstyle' => ['vi' => '<p>Magic Gladiator nhận nhiều điểm mỗi cấp nên mạnh mọi giai đoạn (nhưng không đội được Mũ). Hướng Strength dễ chơi, cày bằng Twisting Slash/Fire Slash; hướng Energy dùng đòn phép. Máu khá, tự cày tốt, hợp <a href="/huong-dan/luyen-cap-offline">luyện cấp offline</a>.</p><ul><li><strong>Đầu game:</strong> chọn dứt khoát 1 hướng (Str hoặc Energy) và dồn vào đó.</li><li><strong>Giữa game:</strong> có thể lai Str+Energy nếu muốn linh hoạt; farm Blood Castle lấy đồ Excellent.</li><li><strong>Cuối game:</strong> nhờ dư điểm, thêm Vitality để trụ PvP dễ hơn các class khác.</li></ul>', 'en' => '<p>The Magic Gladiator gains more points per level, so it is strong throughout (but cannot wear a Helm). Strength is the easy build, farming with Twisting Slash/Fire Slash; Energy uses spells. Decent HP, farms well, suits <a href="/en/huong-dan/luyen-cap-offline">offline leveling</a>.</p><ul><li><strong>Early:</strong> firmly pick one path (Str or Energy) and commit to it.</li><li><strong>Mid:</strong> a Str+Energy hybrid is possible for flexibility; farm Blood Castle for Excellent gear.</li><li><strong>Late:</strong> the extra points let you add Vitality for easier PvP than most classes.</li></ul>'],
                'pvp' => ['vi' => '<p>Cơ động và sát thương cao ở cả hai hướng; nhờ dư điểm nên thêm được nhiều Vitality, trâu hơn Wizard thuần. Fire Slash/Power Slash quấy rối tầm xa rồi áp sát bằng đòn kiếm. Điểm yếu: build 2 tay không có khiên nên chống chịu kém hơn Dark Knight.</p><ul><li><strong>Vai trò party:</strong> sát thương linh hoạt, cày nhanh, tự lo tốt.</li><li><strong>PvP:</strong> đổi đòn xa/gần linh hoạt; giữ Vitality để đứng lâu.</li></ul>', 'en' => '<p>Mobile with high damage in both directions; the spare points allow lots of Vitality, tankier than a pure Wizard. Harass at range with Fire Slash/Power Slash then close in with sword skills. Weakness: a two-handed build has no shield, so it takes more damage than a Dark Knight.</p><ul><li><strong>Party role:</strong> flexible damage, fast farming, self-sufficient.</li><li><strong>PvP:</strong> swap between ranged and melee; keep Vitality to last.</li></ul>'],
            ],
            [
                'slug' => 'dark-lord', 'class_key' => 'dark-lord', 'icon' => 'crown', 'order' => 5,
                'title' => 'Dark Lord', 'name' => 'Dark Lord',
                'excerpt_vi' => 'Set đồ, wings và cách cộng điểm cho Dark Lord.', 'excerpt_en' => 'Gear, wings and stat build for the Dark Lord.',
                'intro' => ['vi' => 'Dark Lord là class "chúa tể": có thú cưỡi (ngựa/quạ), buff đồng đội và đòn AoE mạnh. Chỉ huy party rất tốt.', 'en' => 'The Dark Lord is the "lord" class: it has a mount (horse/raven), buffs allies and hits hard in AoE. Excellent party leader.'],
                'weapon' => ['vi' => 'Gậy quyền (Scepter) + Khiên, cùng Dark Raven (quạ). Scepter Excellent tăng sát thương pet.', 'en' => 'Scepter + Shield, plus the Dark Raven pet. Excellent scepters boost pet damage.'],
                'tiers' => ['starter' => ['Leather (10)', 'Bronze (18)', 'Scale (28)'], 'mid' => ['Light Plate (62)', 'Adamantine (78)'], 'high' => ['Dark Steel (96)', 'Glorious (105)', 'Dark Master (117)'], 'top' => ['Sunlight (147)']],
                'wings' => [['13_30', 'Cape of Lord', ['vi' => 'cấp 2 (Dark Lord không có wing cấp 1)', 'en' => 'level 2 (Dark Lord has no level-1 wing)']], ['12_40', 'Cape of Emperor', $lv(3)]],
                'build' => ['vi' => '<ul><li><strong>Command</strong>: chỉ số riêng, tăng sát thương thú/triệu hồi, dồn nhiều.</li><li><strong>Strength/Vitality</strong>: để trụ.</li></ul>', 'en' => '<ul><li><strong>Command</strong>: the unique stat, boosts pet/summon damage, put the most here.</li><li><strong>Strength/Vitality</strong>: for survivability.</li></ul>'],
                'skills' => ['vi' => '<p>Dark Lord đánh bằng Scepter và cưỡi ngựa/quạ. Kỹ năng đáng chú ý:</p><ul><li><strong>Earthshake (Địa chấn)</strong>: AoE cực lớn khi cưỡi ngựa, quét cả màn hình quái, sát thương tính theo Command, đòn cày biểu tượng của class.</li><li><strong>Fire Burst (Hoả cầu)</strong>: đòn đơn nhiều hit, có thể gây choáng khi lên mastery.</li><li><strong>Fire Scream, Electric Spike</strong>: các đòn AoE khác.</li></ul><p><strong>Kỹ năng Mệnh lệnh (Command):</strong> <strong>Summon</strong> triệu hồi quái làm lính đánh cùng (cần đủ Command), <strong>Increase Critical Damage</strong> buff sát thương chí mạng cho cả party (chỉ Dark Lord mới có). <strong>Dark Raven</strong> (quạ) là pet đánh phụ.</p>', 'en' => '<p>The Dark Lord fights with a Scepter while mounted on a horse/raven. Notable skills:</p><ul><li><strong>Earthshake</strong>: a huge AoE while mounted, sweeps whole screens, damage scaled by Command, the signature farming skill.</li><li><strong>Fire Burst</strong>: multi-hit single-target, can stun at mastery.</li><li><strong>Fire Scream, Electric Spike</strong>: other AoE attacks.</li></ul><p><strong>Command skills:</strong> <strong>Summon</strong> calls a monster to fight for you (needs enough Command), <strong>Increase Critical Damage</strong> buffs the whole party\'s critical damage (Dark Lord only). The <strong>Dark Raven</strong> pet adds extra hits.</p>'],
                'playstyle' => ['vi' => '<p>Cưỡi ngựa + Earthshake quét cả màn hình khiến Dark Lord là một trong những class cày nhanh nhất, lại có pet đánh phụ và rất trâu. Dễ chơi, tuyệt vời cho <a href="/huong-dan/luyen-cap-offline">luyện cấp offline</a> ở bãi đông quái.</p><ul><li><strong>Đầu game:</strong> dồn Command (tăng sát thương Earthshake và pet), Strength/Stamina đủ trụ.</li><li><strong>Giữa game:</strong> sắm ngựa và Scepter tốt; farm Blood Castle, Devil Square.</li><li><strong>Cuối game:</strong> đủ Command để mở Summon và buff crit, thêm Vitality cho PvP.</li></ul>', 'en' => '<p>Mounted Earthshake sweeps the screen, making the Dark Lord one of the fastest farmers, plus a pet and high bulk. Easy to play and excellent for <a href="/en/huong-dan/luyen-cap-offline">offline leveling</a> in dense spots.</p><ul><li><strong>Early:</strong> stack Command (boosts Earthshake and pet), with Strength/Stamina to survive.</li><li><strong>Mid:</strong> get a horse and a good Scepter; farm Blood Castle and Devil Square.</li><li><strong>Late:</strong> reach enough Command for Summon and the crit buff, add Vitality for PvP.</li></ul>'],
                'pvp' => ['vi' => '<p>Dark Lord là "chỉ huy": buff <strong>Increase Critical Damage</strong> tăng chí mạng cho cả party (giá trị cực lớn ở Castle Siege và PvP tập thể), Earthshake AoE mạnh, thân trâu. Điểm yếu: sát thương đơn mục tiêu yếu hơn nếu đối thủ né được AoE.</p><ul><li><strong>Vai trò party:</strong> buff crit + AoE + trâu, trưởng nhóm và PvP tập thể lý tưởng.</li><li><strong>PvP:</strong> Earthshake diện rộng, giữ ngựa để vừa cơ động vừa trâu.</li></ul>', 'en' => '<p>The Dark Lord is the commander: <strong>Increase Critical Damage</strong> buffs the whole party\'s crit (hugely valuable in Castle Siege and group PvP), strong Earthshake AoE, tanky body. Weakness: weaker single-target if the enemy dodges the AoE.</p><ul><li><strong>Party role:</strong> crit buff + AoE + bulk, the ideal leader and group-PvP anchor.</li><li><strong>PvP:</strong> wide Earthshake, stay mounted for mobility and bulk.</li></ul>'],
            ],
            [
                'slug' => 'summoner', 'class_key' => 'summoner', 'icon' => 'wand-sparkles', 'order' => 6,
                'title' => 'Summoner', 'name' => 'Summoner',
                'excerpt_vi' => 'Set đồ, wings và cách cộng điểm cho Summoner.', 'excerpt_en' => 'Gear, wings and stat build for the Summoner.',
                'intro' => ['vi' => 'Summoner là pháp sư nữ chuyên phép nguyền rủa (curse) gây sát thương theo thời gian và khống chế. Debuff mạnh trong PvP.', 'en' => 'The Summoner is a female mage specializing in curse spells (damage over time) and crowd control. Strong debuffs in PvP.'],
                'weapon' => ['vi' => 'Gậy và Sách (Stick/Book). Ưu tiên vũ khí Excellent.', 'en' => 'Sticks and Books. Prefer Excellent weapons.'],
                'tiers' => ['starter' => ['Mystery (34)'], 'mid' => ['Red Wing (56)', 'Ancient (75)'], 'high' => ['Black Rose (91)', 'Lilium (113)'], 'top' => ['Aura (122)']],
                'wings' => [['12_41', 'Wings of Curse', $lv(1)], ['12_42', 'Wings of Despair', $lv(2)], ['12_43', 'Wing of Dimension', $lv(3)]],
                'build' => ['vi' => '<ul><li><strong>Energy</strong>: sát thương phép/curse, dồn nhiều nhất.</li><li><strong>Vitality</strong>: để sống.</li><li><strong>Agility/Strength</strong>: đủ mặc đồ.</li></ul>', 'en' => '<ul><li><strong>Energy</strong>: magic/curse damage, put the most here.</li><li><strong>Vitality</strong>: to survive.</li><li><strong>Agility/Strength</strong>: just enough for gear.</li></ul>'],
                'skills' => ['vi' => '<p>Summoner đánh bằng phép và phép nguyền (curse); đòn curse mạnh nhất đến từ các quyển Sách (Book). Kỹ năng đáng chú ý:</p><ul><li><strong>Chain Lightning (Sấm dây chuyền)</strong>: tia sét nảy qua nhiều mục tiêu, AoE dọn cụm quái.</li><li><strong>Drain Life (Hút máu)</strong>: gây sát thương và hồi máu cho bản thân, cày cực bền.</li><li><strong>Explosion, Requiem, Pollution</strong>: đòn nguyền học từ Sách (Book of Samut / Neil / Lagle).</li><li><strong>Fire Ball, Lightning, Power Wave</strong>: phép cơ bản.</li></ul><p><strong>Khống chế &amp; buff:</strong> <strong>Sleep</strong> ru ngủ mục tiêu (khống chế PvP mạnh), <strong>Weakness</strong> giảm sức mạnh địch, <strong>Innovation</strong> giảm phòng thủ địch, <strong>Berserker</strong> tăng sát thương (đổi lại nhận nhiều damage hơn).</p>', 'en' => '<p>The Summoner uses spells and curses; the strongest curses come from Books. Notable skills:</p><ul><li><strong>Chain Lightning</strong>: lightning that jumps between targets, AoE for clearing packs.</li><li><strong>Drain Life</strong>: deals damage and heals you, farms very sustainably.</li><li><strong>Explosion, Requiem, Pollution</strong>: curses learned from Books (Book of Samut / Neil / Lagle).</li><li><strong>Fire Ball, Lightning, Power Wave</strong>: basic spells.</li></ul><p><strong>Control &amp; buffs:</strong> <strong>Sleep</strong> puts a target to sleep (strong PvP control), <strong>Weakness</strong> lowers enemy power, <strong>Innovation</strong> lowers enemy defense, <strong>Berserker</strong> raises your damage (at the cost of taking more).</p>'],
                'playstyle' => ['vi' => '<p>Summoner là pháp sư curse: Drain Life vừa đánh vừa hồi máu nên cày rất bền, Chain Lightning dọn cụm quái. Máu mỏng như Wizard, đánh tầm xa. Nhờ Drain Life tự hồi máu nên hợp <a href="/huong-dan/luyen-cap-offline">luyện cấp offline</a> lâu dài.</p><ul><li><strong>Đầu game:</strong> dồn Energy, chỉ cộng Agi/Str đủ mặc đồ.</li><li><strong>Giữa game:</strong> thêm ít Vitality; cắm quyển Sách curse phù hợp; farm Blood Castle.</li><li><strong>Cuối game:</strong> Berserker + đồ hồi HP/MP để vừa cày vừa PvP.</li></ul>', 'en' => '<p>The Summoner is a curse mage: Drain Life damages and heals at once for very sustainable farming, and Chain Lightning clears packs. Squishy like a Wizard, fights at range. Because Drain Life self-heals, it suits long <a href="/en/huong-dan/luyen-cap-offline">offline leveling</a>.</p><ul><li><strong>Early:</strong> stack Energy, add only enough Agi/Str for gear.</li><li><strong>Mid:</strong> add a little Vitality; equip the right curse Book; farm Blood Castle.</li><li><strong>Late:</strong> Berserker + HP/MP-recovery gear to both farm and PvP.</li></ul>'],
                'pvp' => ['vi' => '<p>Summoner là "vua khống chế": Sleep ru ngủ đối thủ, Weakness và Innovation bào sức mạnh cùng phòng thủ địch, curse gây sát thương theo thời gian, cực khó chịu trong PvP tay đôi lẫn Castle Siege. Điểm yếu: máu mỏng, phụ thuộc vào việc giữ khoảng cách.</p><ul><li><strong>Vai trò party:</strong> debuff địch (Weakness/Innovation) và sát thương diện rộng.</li><li><strong>PvP:</strong> mở Sleep rồi chồng curse, kite liên tục; bật Berserker khi an toàn để tăng damage.</li></ul>', 'en' => '<p>The Summoner is the king of control: Sleep disables enemies, Weakness and Innovation strip enemy power and defense, and curses tick damage over time, miserable to face in 1v1 and Castle Siege. Weakness: low HP, relies on keeping distance.</p><ul><li><strong>Party role:</strong> enemy debuffs (Weakness/Innovation) and AoE damage.</li><li><strong>PvP:</strong> open with Sleep then stack curses, kite constantly; use Berserker when safe for more damage.</li></ul>'],
            ],
            [
                'slug' => 'rage-fighter', 'class_key' => 'rage-fighter', 'icon' => 'hand-fist', 'order' => 7,
                'title' => 'Rage Fighter', 'name' => 'Rage Fighter',
                'excerpt_vi' => 'Set đồ, wings và cách cộng điểm cho Rage Fighter.', 'excerpt_en' => 'Gear, wings and stat build for the Rage Fighter.',
                'intro' => ['vi' => 'Rage Fighter là võ sĩ cận chiến dùng găng đấm, ra đòn nhanh, nhiều kỹ năng khống chế và combo. Rất mạnh PvP tay đôi.', 'en' => 'The Rage Fighter is a melee brawler using gloves, fast hits with lots of crowd control and combos. Very strong in 1v1 PvP.'],
                'weapon' => ['vi' => 'Găng đấm (Knuckle). Chọn loại theo hướng sát thương.', 'en' => 'Gloves (knuckles). Pick the type that fits your damage build.'],
                'tiers' => ['starter' => ['Leather (10)', 'Scale (28)'], 'mid' => ['Brass (38)', 'Plate (48)', 'Sacred (66)'], 'high' => ['Storm Hard (82)', 'Piercing (101)'], 'top' => ['Phoenix Soul (143)']],
                'wings' => [['12_49', 'Cape of Fighter', ['vi' => 'cấp 2 (Rage Fighter không có wing cấp 1)', 'en' => 'level 2 (Rage Fighter has no level-1 wing)']], ['12_50', 'Cape of Overrule', $lv(3)]],
                'build' => ['vi' => '<ul><li><strong>Strength</strong>: sát thương chính.</li><li><strong>Vitality</strong>: cao, võ sĩ cần trụ.</li><li><strong>Agility</strong>: đủ mặc đồ.</li></ul>', 'en' => '<ul><li><strong>Strength</strong>: main damage.</li><li><strong>Vitality</strong>: high, brawlers need to tank.</li><li><strong>Agility</strong>: enough for gear.</li></ul>'],
                'skills' => ['vi' => '<p>Rage Fighter dùng Găng (Knuckle), ra đòn nhanh và nhiều combo. Kỹ năng đáng chú ý:</p><ul><li><strong>Killing Blow, Beast Uppercut</strong>: combo cận chiến cơ bản, ra đòn nhanh.</li><li><strong>Dragon Roar</strong>: đòn AoE quanh người, dọn cụm quái.</li><li><strong>Chain Drive</strong>: lao tới mục tiêu ở tầm xa hơn (mở ở cấp cao).</li><li><strong>Phoenix Shot</strong>: đòn AoE khác.</li></ul><p><strong>Buff:</strong> <strong>Increase Health</strong> tăng máu cho cả party, <strong>Increase Block</strong> tăng tỉ lệ đỡ đòn, <strong>Ignore Defense</strong> tung đòn bỏ qua phòng thủ đối phương.</p>', 'en' => '<p>The Rage Fighter uses gloves (knuckles), with fast hits and many combos. Notable skills:</p><ul><li><strong>Killing Blow, Beast Uppercut</strong>: basic melee combos, fast strikes.</li><li><strong>Dragon Roar</strong>: an AoE around you, clears packs.</li><li><strong>Chain Drive</strong>: dashes to a target at longer range (unlocks at higher level).</li><li><strong>Phoenix Shot</strong>: another AoE.</li></ul><p><strong>Buffs:</strong> <strong>Increase Health</strong> raises the whole party\'s HP, <strong>Increase Block</strong> raises block rate, <strong>Ignore Defense</strong> lands hits that bypass enemy defense.</p>'],
                'playstyle' => ['vi' => '<p>Rage Fighter ra đòn nhanh, Dragon Roar quét quanh người để cày cụm, máu khá nên tự trụ tốt; Chain Drive lao vào mục tiêu. Hợp <a href="/huong-dan/luyen-cap-offline">luyện cấp offline</a> ở bãi đông quái.</p><ul><li><strong>Đầu game:</strong> dồn Strength, cộng Agility đủ mặc đồ.</li><li><strong>Giữa game:</strong> thêm Vitality (võ sĩ cần trụ); farm Blood Castle lấy Găng Excellent.</li><li><strong>Cuối game:</strong> Vitality cao + Ignore Defense để bước vào PvP.</li></ul>', 'en' => '<p>The Rage Fighter strikes fast, Dragon Roar sweeps around you to farm packs, and decent HP keeps it self-sufficient; Chain Drive dashes onto targets. Suits <a href="/en/huong-dan/luyen-cap-offline">offline leveling</a> in dense spots.</p><ul><li><strong>Early:</strong> stack Strength, add Agility for gear.</li><li><strong>Mid:</strong> add Vitality (brawlers must tank); farm Blood Castle for Excellent gloves.</li><li><strong>Late:</strong> high Vitality + Ignore Defense to step into PvP.</li></ul>'],
                'pvp' => ['vi' => '<p>Một trong những class PvP tay đôi mạnh nhất: nhiều đòn khống chế và combo, Ignore Defense xuyên qua phòng thủ đối phương, lại buff Increase Health cho cả nhóm và thân trâu. Điểm yếu: đánh gần, cần áp sát, nhưng Chain Drive giúp lao vào.</p><ul><li><strong>Vai trò party:</strong> buff máu (Increase Health) + tuyến đầu chịu đòn.</li><li><strong>PvP:</strong> combo khống chế, dùng Ignore Defense để hạ mục tiêu thủ cao; giữ Vitality để trụ.</li></ul>', 'en' => '<p>One of the strongest 1v1 PvP classes: lots of control and combos, Ignore Defense punches through enemy defense, plus an Increase Health party buff and a tanky body. Weakness: short range, needs to close in, but Chain Drive dashes onto targets.</p><ul><li><strong>Party role:</strong> HP buff (Increase Health) + front-line body.</li><li><strong>PvP:</strong> chain control, use Ignore Defense to break high-defense targets; keep Vitality to survive.</li></ul>'],
            ],
        ];

        $out = [];
        foreach ($defs as $d) {
            $d['tierlabels'] = $tl;
            $out[] = [
                'slug' => $d['slug'], 'category' => 'class', 'class_key' => $d['class_key'], 'icon' => $d['icon'],
                'title_vi' => $d['title'], 'title_en' => $d['title'],
                'excerpt_vi' => $d['excerpt_vi'], 'excerpt_en' => $d['excerpt_en'],
                'body_vi' => $this->classBody($d, 'vi'), 'body_en' => $this->classBody($d, 'en'),
                'sort_order' => $d['order'], 'is_published' => true,
            ];
        }

        return $out;
    }
}
