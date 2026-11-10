# Responsive Controls — thim-core Customizer

Hướng dẫn khai báo và sử dụng tính năng **responsive** trong hệ thống customizer của thim-core.

---

## Tổng quan

Khi bật responsive, mỗi setting lưu một object `{ desktop, tablet, mobile }` thay vì một scalar.
Người dùng chọn thiết bị qua bộ icon trong label → thay đổi sẽ chỉ áp dụng cho thiết bị đó.

**Backward compatible:** Dữ liệu cũ (scalar) được tự động chuẩn hóa thành `{ desktop: oldValue, tablet: '', mobile: '' }` — không mất dữ liệu của khách hàng cũ.

---

## Các field hỗ trợ responsive

| Field type | `'responsive' => true` | Vị trí tabs |
|---|---|---|
| `thim-slider` | ✅ | Trong wrapper control |
| `thim-dimension` | ✅ | Trong label (standalone) |
| `thim-dimensions` | ✅ | Trong label của group (1 bộ tabs chung) |
| `thim-typography` | ✅ | Trong label của group (1 bộ tabs chung — chỉ font-size & line-height) |

---

## Cách khai báo

### 1. Slider

Thêm `'responsive' => true` vào `choices`:

```php
thim_customizer()->add_field(
    array(
        'id'        => 'my_font_size',
        'type'      => 'thim-slider',
        'label'     => esc_html__( 'Font Size', 'textdomain' ),
        'section'   => 'my_section',
        'default'   => 16,
        'transport' => 'auto',
        'choices'   => array(
            'min'        => 10,
            'max'        => 100,
            'step'       => 1,
            'suffix'     => 'px',
            'responsive' => true,   // ← bật responsive
        ),
        'output' => array(
            array(
                'element'  => 'body',
                'property' => 'font-size',
                'suffix'   => 'px',
            ),
        ),
    )
);
```

**Giá trị lưu trong DB:**
```json
{ "desktop": 16, "tablet": 14, "mobile": 12 }
```

---

### 2. Dimension (single)

```php
thim_customizer()->add_field(
    array(
        'id'        => 'my_padding_top',
        'type'      => 'thim-dimension',
        'label'     => esc_html__( 'Padding Top', 'textdomain' ),
        'section'   => 'my_section',
        'default'   => '20px',
        'transport' => 'auto',
        'choices'   => array(
            'responsive' => true,   // ← bật responsive
        ),
        'output' => array(
            array(
                'element'  => '.my-element',
                'property' => 'padding-top',
            ),
        ),
    )
);
```

**Giá trị lưu trong DB:**
```json
{ "desktop": "20px", "tablet": "15px", "mobile": "10px" }
```

---

### 3. Dimensions (group nhiều sub-field)

Chỉ cần `'responsive' => true` ở level cha — **tất cả** sub-field trong group sẽ responsive, dùng chung 1 bộ tabs đặt trong label.

```php
thim_customizer()->add_field(
    array(
        'id'        => 'my_padding',
        'type'      => 'thim-dimensions',
        'label'     => esc_html__( 'Padding', 'textdomain' ),
        'section'   => 'my_section',
        'default'   => array(
            'top'    => '0px',
            'right'  => '0px',
            'bottom' => '0px',
            'left'   => '0px',
        ),
        'transport' => 'auto',
        'choices'   => array(
            'responsive' => true,   // ← 1 bộ tabs chung cho tất cả sub-field
        ),
        'output' => array(
            array(
                'element'  => '.my-element',
                'property' => 'padding',
            ),
        ),
    )
);
```

**Giá trị lưu trong DB (mỗi sub-field):**
```json
// my_padding[top]
{ "desktop": "20px", "tablet": "10px", "mobile": "5px" }

// my_padding[right]
{ "desktop": "15px", "tablet": "10px", "mobile": "5px" }
```

> **Nâng cao:** Nếu chỉ muốn một số key cụ thể responsive (mỗi key có tabs riêng), dùng `responsive_choices`:
> ```php
> 'choices' => array(
>     'responsive_choices' => array( 'top', 'bottom' ),
> ),
> ```

---

### 4. Typography

Chỉ cần `'responsive' => true` ở level cha — font-size và line-height sẽ tự động responsive, dùng chung 1 bộ tabs trong label group.

```php
thim_customizer()->add_field(
    array(
        'id'        => 'my_heading_font',
        'type'      => 'thim-typography',
        'label'     => esc_html__( 'Heading Font', 'textdomain' ),
        'section'   => 'my_section',
        'default'   => array(
            'font-family' => 'inherit',
            'variant'     => 'regular',
            'font-size'   => '24px',
            'line-height' => '1.5',
        ),
        'transport' => 'auto',
        'choices'   => array(
            'responsive' => true,   // ← font-size & line-height sẽ responsive
        ),
        'output' => array(
            array(
                'element' => 'h1, h2, h3',
            ),
        ),
    )
);
```

**Giá trị lưu trong DB:**
- `my_heading_font[font-size]` → `{ "desktop": "24px", "tablet": "20px", "mobile": "16px" }`
- `my_heading_font[line-height]` → `{ "desktop": "1.5", "tablet": "1.4", "mobile": "1.3" }`
- `my_heading_font[font-family]` → `"Roboto"` *(không responsive, lưu scalar bình thường)*

---

## CSS Output (postMessage / auto transport)

Hệ thống tự động sinh CSS với media query khi giá trị là object responsive:

```css
/* Desktop (default, không có media query) */
.my-element { font-size: 24px; }

/* Tablet */
@media (max-width: 1024px) {
    .my-element { font-size: 20px; }
}

/* Mobile */
@media (max-width: 768px) {
    .my-element { font-size: 16px; }
}
```

> Device có giá trị rỗng (`""`) sẽ **bỏ qua**, không sinh CSS.

---

## Đọc giá trị trong PHP (theme template)

```php
$font_size = get_theme_mod( 'my_font_size' );

// Responsive → là array
if ( is_array( $font_size ) ) {
    $desktop = $font_size['desktop'] ?? '';
    $tablet  = $font_size['tablet']  ?? '';
    $mobile  = $font_size['mobile']  ?? '';
} else {
    // Scalar (legacy hoặc không bật responsive)
    $desktop = $font_size;
}
```

---

## Lưu ý

- Chỉ có **slider**, **dimension**, **dimensions**, **typography** hỗ trợ responsive.
- Khi bật responsive trên một field đã có dữ liệu cũ (scalar), dữ liệu cũ sẽ tự động được coi là giá trị **desktop** — không cần migration.
- Không khai báo `responsive: true` trên cả parent lẫn từng sub-field riêng lẻ trong cùng một group — chỉ cần ở parent.

---

---

# Layout Builder (thim-builder)

Hệ thống builder cho phép kéo-thả các element header/footer vào các zone của từng row, với hỗ trợ đa thiết bị (desktop/mobile/…).

---

## Tổng quan kiến trúc

```
PHP (theme)
  └── thim_customizer()->add_field('thim-builder')   ← control builder
  └── thim_customizer()->add_section('main')          ← row settings
  └── thim_customizer()->add_section('header_search') ← item settings

JS (customizer panel)
  └── BuilderComponent  ← quản lý state layout
      └── RowComponent (×N rows)
          └── DropComponent (×N zones)
              ├── ItemComponent  ← chip có gear → focusItem(section)
              └── AddComponent   ← popup để add item

JS (preview iframe)
  └── preview.js  ← selective refresh / edit shortcut
```

**Canvas** là một `<ul id="thim-builder-canvas-{control_id}">` element được render fixed ở cuối màn hình, bên ngoài WP Customizer container. Canvas hiển thị khi panel được mở, và vẫn visible khi user navigate sang section của một choice item (kể cả section nằm ngoài panel như Widgets).

---

## Đăng ký control (PHP)

Dùng `thim_customizer()` API — **không dùng `$wp_customize` trực tiếp**.

### 1. Tạo panel

```php
thim_customizer()->add_panel( array(
    'id'       => 'header',
    'title'    => esc_html__( 'Header', 'textdomain' ),
    'priority' => 30,
) );
```

### 2. Tạo section chứa builder control

```php
thim_customizer()->add_section( array(
    'id'       => 'header_layout',
    'title'    => esc_html__( 'Header Layout', 'textdomain' ),
    'panel'    => 'header',
    'priority' => 20,
) );
```

### 3. Đăng ký builder control

Setting, control và choices đều khai báo trong một lần gọi `add_field()`:

```php
thim_customizer()->add_field( array(
    'id'          => 'header_builder',       // ← setting ID = groupKey
    'type'        => 'thim-builder',
    'section'     => 'header_layout',

    // Default layout
    'default'     => array(
        'desktop' => array(
            'top'    => array( 'left' => array(), 'center' => array(), 'right' => array() ),
            'main'   => array( 'left' => array( 'logo' ), 'center' => array(), 'right' => array( 'navigation' ) ),
            'bottom' => array( 'left' => array(), 'center' => array(), 'right' => array() ),
        ),
        'mobile'  => array(
            'main' => array( 'left' => array( 'logo' ), 'center' => array(), 'right' => array() ),
        ),
    ),

    // Available items — key là ID, value là metadata
    'choices'     => array(
        'logo'       => array( 'name' => 'Logo',               'section' => 'title_tagline' ),
        'navigation' => array( 'name' => 'Primary Navigation', 'section' => 'nav_menus_created_posts' ),
        'search'     => array( 'name' => 'Search',             'section' => 'header_search' ),
        'cart'       => array( 'name' => 'Cart',               'section' => 'header_cart' ),
        // Widget sidebar: section ID = 'sidebar-widgets-{sidebar_id}'
        'menu_right' => array( 'name' => 'Menu Right',         'section' => 'sidebar-widgets-menu_right' ),
    ),

    'input_attrs' => array(
        'group'   => 'header_builder',       // phải trùng với 'id'
        'devices' => array( 'desktop', 'tablet' ),

        'rows'    => array(
            'desktop' => array( 'top', 'main', 'bottom' ),
            'tablet'  => array( 'top', 'main', 'bottom' ),
        ),

        'zones'   => array(
            'desktop' => array(
                'top'    => array( 'left', 'center', 'right' ),
                'main'   => array( 'left', 'center', 'right' ),
                'bottom' => array( 'left', 'center', 'right' ),
            ),
            'tablet'  => array(
                'top'    => array( 'left', 'center', 'right' ),
                'main'   => array( 'left', 'center', 'right' ),
                'bottom' => array( 'left', 'center', 'right' ),
            ),
        ),

        // (Tuỳ chọn) Nhãn cho row
        'row_labels'  => array(
            'desktop' => array(
                'top'    => 'Top Row',
                'main'   => 'Main Row',
                'bottom' => 'Bottom Row',
            ),
        ),

        // (Tuỳ chọn) Nhãn cho từng zone
        'zone_labels' => array(
            'desktop' => array(
                'main' => array( 'left' => 'Left', 'center' => 'Center', 'right' => 'Right' ),
            ),
        ),

        // Section IDs ẩn khỏi sidebar panel list (chỉ accessible qua gear icon)
        'hidden_sections' => array( 'main', 'top', 'bottom', 'header_search' ),
        // Chon 1 row làm canvas trong mobile
        'canvas_rows'     => array( 'bottom' ),
    ),
) );
```

---

## `input_attrs` — Tham số đầy đủ

| Tham số | Kiểu | Bắt buộc | Mô tả |
|---|---|---|---|
| `group` | `string` | Không | Key tra cứu choices. Mặc định = control ID. |
| `devices` | `string[]` | **Có** | Danh sách thiết bị. Tab đầu tiên là tab mặc định. |
| `rows` | `Record<device, string[]>` | **Có** | Danh sách row theo từng device. |
| `zones` | `Record<device, Record<row, string[]>>` | **Có** | Danh sách zone theo từng device/row. |
| `row_labels` | `Record<device, Record<row, string>>` | Không | Nhãn hiển thị cho từng row (hover tooltip trên gear icon). |
| `zone_labels` | `Record<device, Record<row, Record<zone, string>>>` | Không | Nhãn hiển thị cho từng zone. |
| `hidden_sections` | `string[]` | Không | Section IDs bị ẩn khỏi sidebar panel list (chỉ áp dụng section cùng panel). |

---

## `choices` — Cấu trúc mỗi item

| Field | Bắt buộc | Mô tả |
|---|---|---|
| `name` | **Có** | Nhãn hiển thị trên chip trong canvas và pool. |
| `section` | Không | Section ID mở ra khi user click icon ⚙ trên chip. |

**Widget sidebars:** Khi đăng ký `register_sidebar( array( 'id' => 'menu_right' ) )`, WP Customizer tự tạo section với ID `sidebar-widgets-menu_right`. Dùng ID này làm `section` trong choices — canvas sẽ vẫn visible khi user mở section đó dù nó nằm trong Widgets panel.

---

## `hidden_sections` — Ẩn section khỏi sidebar

Các section trong `hidden_sections` sẽ **không hiển thị** trong danh sách navigation của panel sidebar, nhưng vẫn hoạt động đầy đủ khi được mở qua icon ⚙ trong canvas.

**Cơ chế:** Chỉ ẩn thẻ `<h3 class="accordion-section-title">` bằng `display:none !important`. Section container vẫn tồn tại và `section.focus()` vẫn hoạt động.

> Widget sections (`sidebar-widgets-*`) nằm trong Widgets panel — không thể thêm vào `hidden_sections` (chỉ áp dụng cho sections cùng panel).

---

## Đăng ký Row Settings

Mỗi row trong builder có icon ⚙ bên trái. Khi click, builder gọi `customizer.section(rowId).focus()` — trong đó `rowId` là key của row (`'top'`, `'main'`, `'bottom'`…).

Cần đăng ký section có ID **trùng với tên row**:

```php
thim_customizer()->add_section( array(
    'id'       => 'main',              // ← phải trùng với key trong 'rows'
    'title'    => __( 'Main Row Settings', 'eduma' ),
    'panel'    => 'header',
    'priority' => 20,
) );

thim_customizer()->add_field( array(
    'id'          => 'header_main_row_height',
    'type'        => 'slider',
    'section'     => 'main',
    'label'       => __( 'Row Height', 'eduma' ),
    'default'     => 80,
    'input_attrs' => array( 'min' => 40, 'max' => 200, 'step' => 1 ),
) );
```

> Nếu section không tồn tại, click gear icon sẽ không làm gì. Section vẫn cần được đăng ký dù không có fields.

---

## Đăng ký Item Settings

Mỗi item trong choices cần một WP Customize section riêng:

```php
thim_customizer()->add_section( array(
    'id'       => 'header_search',
    'title'    => __( 'Search Settings', 'eduma' ),
    'panel'    => 'header',
    'priority' => 30,
) );

thim_customizer()->add_field( array(
    'id'      => 'header_search_style',
    'type'    => 'select',
    'section' => 'header_search',
    'label'   => __( 'Style', 'eduma' ),
    'default' => 'icon',
    'choices' => array(
        'icon'  => __( 'Icon only', 'eduma' ),
        'input' => __( 'Input field', 'eduma' ),
    ),
) );
```

---

## Canvas visibility

- Canvas **show** khi panel được mở.
- Canvas **vẫn visible** khi user navigate sang section của một item — kể cả section nằm ngoài panel (`title_tagline`, Menus panel, Widgets panel).
- Canvas **hide** chỉ khi tất cả section liên quan đều đóng.

```
Mở panel header                    → canvas slide up
  └── Mở section main               → canvas VẪN visible
  └── Click gear logo               → title_tagline mở → canvas VẪN visible
  └── Click gear menu_right         → sidebar-widgets-menu_right mở → canvas VẪN visible
Đóng tất cả section liên quan      → canvas slide down
```

---

## Dữ liệu lưu trong WP Customizer

```
LayoutData = {
  [device: string]: {           // vd: "desktop", "mobile"
    [row: string]: {            // vd: "main", "top"
      [zone: string]: string[]  // vd: "left" → ["logo", "search"]
    }
  }
}
```

**Ví dụ JSON:**

```json
{
  "desktop": {
    "top":    { "left": [],       "center": [],       "right": ["search"] },
    "main":   { "left": ["logo"], "center": [],       "right": ["navigation", "cart"] },
    "bottom": { "left": [],       "center": [],       "right": [] }
  },
  "mobile": {
    "main": { "left": ["logo"], "center": [], "right": [] }
  }
}
```

---

## "Not in use" Pool

Items được định nghĩa trong `choices` nhưng chưa được đặt vào bất kỳ zone nào của device hiện tại sẽ hiển thị trong vùng "Not in use" ở cuối canvas. User có thể kéo item từ pool lên các zone.

---

## Hover edit trong Preview iframe

### Selective Refresh (khuyến nghị)

```php
add_action( 'customize_register', function( $wp_customize ) {
    $wp_customize->selective_refresh->add_partial( 'header_logo_image', array(
        'selector'        => '.site-header .header-logo',
        'render_callback' => 'eduma_render_header_logo',
        'settings'        => array( 'header_logo_image', 'header_logo_width' ),
    ) );
} );
```

WP Customizer tự động thêm icon bút chì vào element trong preview. Click sẽ focus section liên quan.

### Custom postMessage (thủ công)

Trong `preview.js` (chạy trong iframe):

```js
wp.customize.bind( 'preview-ready', function() {
    const itemMap = {
        '.header-logo'        : 'title_tagline',
        '.primary-navigation' : 'nav_menus_created_posts',
        '.header-search'      : 'header_search',
    };
    Object.entries( itemMap ).forEach( ( [ selector, sectionId ] ) => {
        const el = document.querySelector( selector );
        if ( ! el ) return;
        el.addEventListener( 'click', function() {
            wp.customize.preview.send( 'focus-section', sectionId );
        } );
    } );
} );
```

Trong customizer script:

```js
wp.customize.bind( 'ready', function() {
    wp.customize.previewer.bind( 'focus-section', function( sectionId ) {
        const section = wp.customize.section( sectionId );
        if ( section ) section.focus();
    } );
} );
```

---

## Sơ đồ luồng dữ liệu

```
User kéo / add item vào zone
        ↓
BuilderComponent.setValue()
        ↓
useEffect → control.setting.set(value)    [lưu vào WP Customizer]
        ↓
WP Customizer preview transport → Preview iframe re-render header
```

```
User click ⚙ trên chip (item)          User click ⚙ trên Row (bên trái)
        ↓                                        ↓
focusItem( choices[item].section )      focusPanel( rowId )
        ↓                                        ↓
customizer.section('header_search')     customizer.section('main')
        .focus()                                 .focus()
```

---

## Đọc giá trị trong PHP (theme template)

```php
$layout = get_theme_mod( 'header_builder', array() );

// Items trong zone "left" của row "main" trên desktop
$items = $layout['desktop']['main']['left'] ?? array();

foreach ( $items as $item_id ) {
    // render item theo $item_id
}
```

---

## Checklist khi thêm item mới vào builder

- [ ] Thêm vào `choices` trong `add_field()` với `name` và `section`
- [ ] Đăng ký WP Customize section có ID = `section` value ở trên (dùng `thim_customizer()->add_section()`)
- [ ] Thêm fields vào section đó (dùng `thim_customizer()->add_field()`)
- [ ] Nếu muốn ẩn section khỏi sidebar và nó cùng panel → thêm vào `hidden_sections`
- [ ] (Tuỳ chọn) Đăng ký selective refresh partial cho preview live
- [ ] Thêm render logic vào theme template

---

## Build assets

Sau khi sửa các file TS/SCSS trong `inc/customizer/src/layout-builder/`:

```bash
cd Plugins/thim-core/inc/customizer
npm run build
```

File output: `inc/customizer/dist/layout-builder.*`
