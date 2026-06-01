<?php /** @var array $theme @var array $templates */ $pageTitle = 'قالب و ظاهر';
$tnames = ['classic' => 'کلاسیک', 'modern' => 'مدرن', 'elegant' => 'شیک', 'dark' => 'تیره', 'custom' => 'سفارشی (آپلودی)']; ?>
<div class="card form-card">
    <p class="muted">هر مجموعه قالب و رنگ‌بندی مستقل دارد، پس منوی دو مشتری شبیه هم نمی‌شود.</p>
    <form method="post" action="<?= url('admin/theme') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <label>قالب پایه
            <select name="template" id="tplSel" onchange="document.getElementById('customBox').style.display=this.value==='custom'?'block':'none'">
                <?php foreach ($templates as $tpl): ?>
                    <option value="<?= e($tpl) ?>" <?= $theme['template'] === $tpl ? 'selected' : '' ?>><?= $tnames[$tpl] ?? $tpl ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="form-row">
            <label>رنگ اصلی<input type="color" name="primary_color" value="<?= e($theme['primary_color']) ?>"></label>
            <label>رنگ ثانویه<input type="color" name="secondary_color" value="<?= e($theme['secondary_color']) ?>"></label>
            <label>رنگ پس‌زمینه<input type="color" name="bg_color" value="<?= e($theme['bg_color']) ?>"></label>
            <label>رنگ متن<input type="color" name="text_color" value="<?= e($theme['text_color']) ?>"></label>
        </div>

        <label>فونت<input type="text" name="font_family" value="<?= e($theme['font_family']) ?>"></label>

        <div class="form-row">
            <label>عنوان سرصفحه<input type="text" name="hero_title" value="<?= e($theme['hero_title'] ?? '') ?>"></label>
            <label>زیرعنوان<input type="text" name="hero_subtitle" value="<?= e($theme['hero_subtitle'] ?? '') ?>"></label>
        </div>

        <div class="form-row">
            <label>لوگو<input type="file" name="logo" accept="image/*"><?php if (!empty($theme['logo_path'])): ?><img class="preview sm" src="<?= e(url($theme['logo_path'])) ?>"><?php endif; ?></label>
            <label>تصویر سرصفحه<input type="file" name="hero" accept="image/*"><?php if (!empty($theme['hero_image'])): ?><img class="preview sm" src="<?= e(url($theme['hero_image'])) ?>"><?php endif; ?></label>
        </div>

        <label>CSS سفارشی (اختیاری)<textarea name="custom_css" rows="4"><?= e($theme['custom_css'] ?? '') ?></textarea></label>

        <div id="customBox" class="custom-tpl" style="display:<?= ($theme['template'] ?? '') === 'custom' ? 'block' : 'none' ?>">
            <h3>🧩 قالب سفارشی (آپلودی)</h3>
            <p class="muted">یک فایل HTML قالب آپلود کنید یا مستقیم ویرایش کنید. این قالب فقط زمانی نمایش داده می‌شود که «قالب پایه» روی «سفارشی» باشد. داخل قالب از توکن‌های زیر استفاده کنید (همه‌چیز خودکار و ضد‑XSS جای‌گذاری می‌شود):</p>
            <details class="tokens">
                <summary>راهنمای توکن‌ها</summary>
                <pre dir="ltr">{{tenant.name}} {{tenant.phone}} {{tenant.address}} {{currency}}
{{theme.logo}} {{theme.hero_image}} {{theme.hero_title}} {{theme.hero_subtitle}}

{{#has_popular}} ... {{#popular}} ... {{/popular}} ... {{/has_popular}}

{{#categories}}
  &lt;h2&gt;{{name}}&lt;/h2&gt;
  {{#items}}
    &lt;div class="card {{#sold_out}}out{{/sold_out}}"&gt;
      {{#image}}&lt;img src="{{image}}"&gt;{{/image}}
      &lt;h3&gt;{{name}}&lt;/h3&gt;
      &lt;p&gt;{{description}}&lt;/p&gt;
      {{#has_discount}}&lt;s&gt;{{old_price}}&lt;/s&gt; &lt;b&gt;{{discount_label}}&lt;/b&gt;{{/has_discount}}
      &lt;span&gt;{{price}} {{currency}}&lt;/span&gt;
      {{^available}}&lt;span&gt;ناموجود&lt;/span&gt;{{/available}}
      {{#featured}}⭐{{/featured}}  {{#rating}}{{rating}} ({{rating_count}}){{/rating}}
    &lt;/div&gt;
  {{/items}}
{{/categories}}

فرم عضویت:
&lt;form method="post" action="{{register_action}}"&gt;
  &lt;input type="hidden" name="_csrf" value="{{csrf}}"&gt;
  &lt;input name="first_name"&gt; &lt;input name="phone"&gt; &lt;input type="date" name="birthdate"&gt;
&lt;/form&gt;</pre>
            </details>
            <label>آپلود فایل قالب (.html)
                <input type="file" name="custom_html_file" accept=".html,text/html">
            </label>
            <label>یا ویرایش مستقیم HTML قالب
                <textarea name="custom_html" rows="12" dir="ltr" style="font-family:monospace;font-size:.82rem"><?= e($theme['custom_html'] ?? '') ?></textarea>
            </label>
            <small class="muted">CSS قالبت را می‌توانی هم داخل همین HTML با تگ &lt;style&gt; بگذاری، هم در فیلد «CSS سفارشی» بالا.</small>
        </div>

        <button class="btn-primary">ذخیره قالب</button>
    </form>
</div>
