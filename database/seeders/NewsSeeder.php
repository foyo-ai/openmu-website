<?php

namespace Database\Seeders;

use App\Models\News;
use Illuminate\Database\Seeder;

/**
 * Seeds announcement posts. Uses firstOrCreate by slug so a post is only
 * inserted once: later edits made by GMs in the admin panel are never
 * overwritten by a redeploy.
 */
class NewsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->posts() as $p) {
            News::firstOrCreate(['slug' => $p['slug']], array_merge($p, ['author_account_id' => null]));
        }
    }

    private function posts(): array
    {
        return [$this->offlineModeAnnouncement()];
    }

    private function offlineModeAnnouncement(): array
    {
        $bodyVi = <<<'HTML'
<p>Từ hôm nay muss6 hỗ trợ <strong>treo máy offline</strong>: bạn tắt game nhưng nhân vật vẫn tiếp tục hoạt động trên server, với 2 lệnh mới.</p>
<h2>Bán hàng offline: /offstore</h2>
<p>Mở cửa hàng cá nhân như bình thường (xếp đồ, đặt giá, đặt tên shop), gõ <code>/offstore</code> rồi tắt game. Nhân vật đứng nguyên tại chỗ bán hàng cho bạn. Bán hết hàng thì shop tự đóng, toàn bộ zen được lưu ngay vào nhân vật. Hoàn toàn miễn phí.</p>
<h2>Luyện cấp offline: /offlevel</h2>
<p>Bật MU Helper (phím Home) cho đánh quái ổn định, gõ <code>/offlevel</code> rồi tắt game. Nhân vật tiếp tục tự đánh quái, nhặt đồ và dùng buff theo đúng cấu hình MU Helper của bạn. Lệnh này thu một khoản zen khi kích hoạt (theo cấp nhân vật) và trừ dần trong lúc treo.</p>
<h2>Lưu ý nhanh</h2>
<ul>
<li>Mỗi tài khoản chạy được 1 phiên offline tại một thời điểm.</li>
<li>Đăng nhập lại bất cứ lúc nào để dừng, tiến độ được giữ nguyên.</li>
<li>Chỉ cần mở game qua Launcher để tự cập nhật client mới nhất trước khi dùng.</li>
</ul>
<p>Xem hướng dẫn chi tiết từng bước tại <a href="/huong-dan/treo-may-offline">Hướng dẫn: Treo máy offline</a>.</p>
HTML;

        $bodyEn = <<<'HTML'
<p>Starting today muss6 supports <strong>offline mode</strong>: close the game and your character keeps going on the server, with 2 new commands.</p>
<h2>Offline store: /offstore</h2>
<p>Open your personal store as usual (add items, set prices, name the store), type <code>/offstore</code> and close the game. Your character keeps selling on the spot. Once sold out the store closes itself and all earned zen is saved immediately. Completely free.</p>
<h2>Offline leveling: /offlevel</h2>
<p>Start MU Helper (Home key), let it grind stably, type <code>/offlevel</code> and close the game. The character keeps fighting, looting and buffing with your MU Helper settings. This command charges an activation zen fee (based on character level) plus the usual ongoing drain.</p>
<h2>Quick notes</h2>
<ul>
<li>One offline session per account at a time.</li>
<li>Log back in at any time to stop, all progress is kept.</li>
<li>Just start the game through the Launcher to get the latest client first.</li>
</ul>
<p>Step-by-step details in the <a href="/en/huong-dan/treo-may-offline">guide: Offline mode</a>.</p>
HTML;

        return [
            'slug' => 'treo-may-offline-ra-mat',
            'title_vi' => 'Ra mắt treo máy offline: bán hàng /offstore và luyện cấp /offlevel',
            'title_en' => 'Offline mode is live: /offstore selling and /offlevel leveling',
            'excerpt_vi' => 'Tắt game mà nhân vật vẫn đứng bán hàng hoặc tự luyện cấp trên server, với 2 lệnh mới.',
            'excerpt_en' => 'Close the game while your character keeps selling or leveling on the server, with 2 new commands.',
            'body_vi' => $bodyVi,
            'body_en' => $bodyEn,
            'cover_image' => null,
            'is_published' => true,
            'published_at' => '2026-07-04 12:00:00',
        ];
    }
}
