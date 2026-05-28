@props([
    'beverages' => [
        [
            'name' => 'TRÀ MATCHA',
            'title' => 'TRÀ MATCHA THANH MÁT, TINH KHIẾT',
            'price' => '85.000₫',
            'image' => '/images/matcha.png',
            'bg' => '#5d9c59',
            'desc' => 'Hương vị trà xanh Nhật Bản thượng hạng hòa quyện cùng sữa tươi béo ngậy. Một lựa chọn hoàn hảo cho những ai yêu thích sự thanh khiết và tươi mới.'
        ],
        [
            'name' => 'TRÀ SỮA',
            'title' => 'TRÀ SỮA CHÂN TRÂU ĐƯỜNG ĐEN',
            'price' => '75.000₫',
            'image' => '/images/trasua.png', 
            'bg' => '#ffffff',
            'desc' => 'Trà sữa dâu đỏ đậm đà hòa quyện với sữa tươi, nổi bật trên nền trắng tinh khôi và chữ đỏ rực.'
        ],
        [
            'name' => 'CÀ PHÊ Ủ LẠNH',
            'title' => 'CÀ PHÊ Ủ LẠNH ĐẬM ĐÀ, SANG CHẢNH',
            'price' => '65.000₫',
            'image' => '/images/cafe.png',
            'bg' => '#322c2b',
            'desc' => 'Cà phê ủ lạnh 12 giờ cho vị thanh khiết, ít đắng và đượm hương trái cây tự nhiên từ những hạt cà phê Arabica được tuyển chọn kỹ lưỡng.'
        ],
        [
            'name' => 'XOÀI NHIỆT ĐỚI',
            'title' => 'HƯƠNG VỊ NHIỆT ĐỚI MÁT LẠNH',
            'price' => '90.000₫',
            'image' => '/images/sinhtoxoai.png',
            'bg' => '#ffb100',
            'desc' => 'Hương vị xoài chín mọng hòa quyện cùng cốt dừa tươi, mang cả mùa hè nhiệt đới vào từng ngụm nước mát lạnh và thơm nồng nàn.'
        ]
    ]
])

<div class="slider" id="mainSlider">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&family=Oswald:wght@500;700&display=swap');

        :root {
            --item1-transform: translateX(-100%) translateY(-5%) scale(1.5);
            --item1-filter: blur(30px);
            --item1-zIndex: 11;
            --item1-opacity: 0;

            --item2-transform: translateX(0);
            --item2-filter: blur(0);
            --item2-zIndex: 10;
            --item2-opacity: 1;

            --item3-transform: translate(50%, 10%) scale(0.8);
            --item3-filter: blur(10px);
            --item3-zIndex: 9;
            --item3-opacity: 1;

            --item4-transform: translate(90%, 20%) scale(0.5);
            --item4-filter: blur(30px);
            --item4-zIndex: 8;
            --item4-opacity: 1;

            --item5-transform: translate(120%, 30%) scale(0.3);
            --item5-filter: blur(40px);
            --item5-zIndex: 7;
            --item5-opacity: 0;
            
            --transition-speed: 0.8s;
        }

        .slider {
            width: 100%;
            height: 100vh;
            overflow: hidden;
            position: relative;
            background-color: #111;
            font-family: 'Poppins', sans-serif;
            color: #fff;
        }

        .item.item-white .content {
            color: #111;
        }

        .item.item-white .content h1,
        .item.item-white .content h2,
        .item.item-white .content .price,
        .item.item-white .content .description {
            color: #111;
        }

        .item.item-white .btn-order {
            background: #df2e38;
            color: #fff;
        }

        .item.item-white .btn-suggest {
            color: #111;
            border-color: rgba(0,0,0,0.2);
            background: rgba(0,0,0,0.04);
        }


        .list {
            width: 100%;
            height: 100%;
            position: relative;
        }

        .item {
            position: absolute;
            inset: 0;
            display: grid;
            grid-template-columns: 45% 55%;
            transition: 0.5s;
        }

        /* Two-layer JS-driven background system */
        .slider-bg,
        .slider-bg-incoming {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }
        .slider-bg {
            background-color: #5d9c59; /* initial slide color */
            transition: none;
        }
        .slider-bg-incoming {
            clip-path: circle(0% at 50% 50%);
            background-color: transparent;
        }
        .slider-bg-incoming.revealing {
            animation: bgReveal 0.9s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }
        @keyframes bgReveal {
            from { clip-path: circle(0% at 50% 50%); }
            to   { clip-path: circle(150% at 50% 50%); }
        }



        /* Content section on the left */
        .content {
            padding: 60px 10%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            z-index: 15;
            opacity: 0;
            pointer-events: none;
            transition: 0.5s;
            box-sizing: border-box;
        }

        .item:nth-child(2) .content {
            opacity: 1;
            pointer-events: auto;
        }

        .content h1 {
            font-family: 'Oswald', sans-serif;
            font-size: 5rem;
            line-height: 0.9;
            margin: 0;
            text-transform: uppercase;
            transform: translateY(50px);
            filter: blur(20px);
            transition: 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .content h2 {
            font-family: 'Oswald', sans-serif;
            font-size: 2.5rem;
            font-weight: 300;
            margin: 15px 0;
            text-transform: uppercase;
            transform: translateY(50px);
            filter: blur(20px);
            transition: 0.6s 0.1s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .content .price {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0 25px;
            transform: translateY(50px);
            filter: blur(20px);
            transition: 0.6s 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .content .description {
            max-width: 450px;
            line-height: 1.8;
            margin-bottom: 40px;
            font-size: 0.95rem;
            transform: translateY(50px);
            filter: blur(20px);
            transition: 0.6s 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .content .btn-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            transform: translateY(50px);
            filter: blur(20px);
            transition: 0.6s 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .content .btn-order {
            padding: 16px 36px;
            background: var(--btn-bg, var(--drink-primary, #008b7a));
            color: #fff;
            border: none;
            border-radius: 40px;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: background 0.3s, transform 0.2s;
            box-shadow: 0 10px 24px rgba(0,0,0,0.25);
        }

        .content .btn-order:hover {
            background: #006f62;
            transform: translateY(-2px);
            color: #fff;
        }

        .content .btn-suggest {
            padding: 16px 36px;
            background: transparent;
            color: #fff;
            border: 2px solid rgba(255,255,255,0.7);
            border-radius: 40px;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: background 0.3s, border-color 0.3s, transform 0.2s;
        }

        .content .btn-suggest:hover {
            background: rgba(255,255,255,0.15);
            border-color: #fff;
            transform: translateY(-2px);
            color: #fff;
        }

        .item:nth-child(2) .content h1,
        .item:nth-child(2) .content h2,
        .item:nth-child(2) .content .price,
        .item:nth-child(2) .content .description,
        .item:nth-child(2) .content .btn-group {
            transform: translateY(0);
            filter: blur(0);
        }

        /* Image Gallery section on the right */
        .image-gallery {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: visible;
        }

        .product-img {
            width: 480px;
            aspect-ratio: 1;
            object-fit: contain;
            position: absolute;
            transition: var(--transition-speed) cubic-bezier(0.4, 0, 0.2, 1);
            filter: drop-shadow(0 50px 80px rgba(0,0,0,0.5));
        }

        .item:nth-child(1) .product-img {
            transform: var(--item1-transform);
            filter: var(--item1-filter);
            z-index: var(--item1-zIndex);
            opacity: var(--item1-opacity);
        }

        .item:nth-child(2) .product-img {
            transform: var(--item2-transform);
            filter: var(--item2-filter);
            z-index: var(--item2-zIndex);
            opacity: var(--item2-opacity);
        }

        .item:nth-child(3) .product-img {
            transform: var(--item3-transform);
            filter: var(--item3-filter);
            z-index: var(--item3-zIndex);
            opacity: var(--item3-opacity);
        }

        .item:nth-child(4) .product-img {
            transform: var(--item4-transform);
            filter: var(--item4-filter);
            z-index: var(--item4-zIndex);
            opacity: var(--item4-opacity);
        }

        .item:nth-child(n+5) .product-img {
            transform: var(--item5-transform);
            filter: var(--item5-filter);
            z-index: var(--item5-zIndex);
            opacity: var(--item5-opacity);
        }

        /* Responsive Design */
        @media screen and (max-width: 1023px) {
            .product-img { width: 350px; }
            .content h1 { font-size: 3.5rem; }
            .content h2 { font-size: 1.8rem; }
            .slider header { padding: 0 30px; }
        }

        @media screen and (max-width: 767px) {
            .item {
                grid-template-columns: 100%;
                grid-template-rows: 40% 60%;
            }
            .item::after { display: none; }
            .content {
                order: 2;
                padding: 30px;
                text-align: center;
                align-items: center;
            }
            .image-gallery { order: 1; }
            .product-img { width: 260px; }
            .content h1 { font-size: 2.5rem; }
            .content h2 { font-size: 1.5rem; }
            .content .description { display: none; }
            .arrows { left: 50%; bottom: 20px; }
            
            /* Adjust positions for small screens */
            --item3-transform: translate(35%, 15%) scale(0.6);
            --item4-transform: translate(70%, 25%) scale(0.4);
        }
    </style>

    <!-- Two background layers for JS-driven clip-path reveal -->
    <div class="slider-bg" id="sliderBg"></div>
    <div class="slider-bg-incoming" id="sliderBgIncoming"></div>

    <div class="list">
        @foreach($beverages as $item)
            <div class="item{{ $item['name'] === 'TRÀ SỮA' ? ' item-white' : '' }}" style="--bg: {{ $item['bg'] }}; --btn-bg: {{ $item['name'] === 'TRÀ SỮA' ? '#df2e38' : $item['bg'] }};">
                <div class="content">
                    <h1>{{ $item['name'] }}</h1>
                    <h2>{{ $item['title'] }}</h2>
                    <p class="price">{{ $item['price'] }}</p>
                    <p class="description">{{ $item['desc'] }}</p>
                    <div class="btn-group">
                        <a href="{{ route('products.index') }}" class="btn-order">Đặt ngay</a>
                        <a href="#featured-products" class="btn-suggest">Xem gợi ý hôm nay</a>
                    </div>
                </div>
                <div class="image-gallery">
                    <img src="{{ $item['image'] }}" class="product-img" alt="{{ $item['name'] }}">
                </div>
            </div>
        @endforeach
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sliderEl   = document.getElementById('mainSlider');
            const list       = document.querySelector('#mainSlider .list');
            const bgBase     = document.getElementById('sliderBg');
            const bgIncoming = document.getElementById('sliderBgIncoming');

            let isAnimating = false;

            // Read --bg CSS variable from an item element
            const getBgColor = (item) =>
                item.style.getPropertyValue('--bg').trim();

            // Set initial background to the 2nd item (active slide)
            const initItems = list.querySelectorAll('.item');
            if (initItems.length >= 2) {
                bgBase.style.backgroundColor = getBgColor(initItems[1]);
            } else if (initItems.length === 1) {
                bgBase.style.backgroundColor = getBgColor(initItems[0]);
            }

            const triggerBgReveal = (newColor) => {
                // Set incoming layer color and reset animation
                bgIncoming.style.backgroundColor = newColor;
                bgIncoming.classList.remove('revealing');
                // Force reflow so animation re-fires
                void bgIncoming.offsetWidth;
                bgIncoming.classList.add('revealing');

                // After animation completes, swap the base color and hide incoming
                setTimeout(() => {
                    bgBase.style.backgroundColor = newColor;
                    bgIncoming.classList.remove('revealing');
                    bgIncoming.style.backgroundColor = 'transparent';
                }, 920);
            };

            const moveSlider = (direction) => {
                if (isAnimating) return;
                isAnimating = true;

                const items = list.querySelectorAll('.item');

                if (direction === 'next') {
                    list.appendChild(items[0]);
                } else {
                    list.prepend(items[items.length - 1]);
                }

                // After DOM reorder, 2nd child is now the active slide
                const newActive = list.querySelector('.item:nth-child(2)');
                if (newActive) {
                    triggerBgReveal(getBgColor(newActive));
                }

                setTimeout(() => {
                    isAnimating = false;
                }, 800);
            };

            sliderEl.style.touchAction = 'pan-y';

            let autoplayInterval = setInterval(() => moveSlider('next'), 6000);
            const resetAutoplay = () => {
                clearInterval(autoplayInterval);
                autoplayInterval = setInterval(() => moveSlider('next'), 6000);
            };

            let pointerStartX = null;

            const onPointerDown = (event) => {
                if (event.pointerType === 'mouse' && event.button !== 0) return;
                pointerStartX = event.clientX;
                sliderEl.setPointerCapture(event.pointerId);
            };

            const onPointerUp = (event) => {
                if (pointerStartX === null) return;
                const deltaX = event.clientX - pointerStartX;
                pointerStartX = null;
                sliderEl.releasePointerCapture(event.pointerId);

                if (Math.abs(deltaX) < 50) return;

                if (deltaX < 0) {
                    moveSlider('next');
                } else {
                    moveSlider('prev');
                }

                resetAutoplay();
            };

            sliderEl.addEventListener('pointerdown', onPointerDown);
            sliderEl.addEventListener('pointerup', onPointerUp);
            sliderEl.addEventListener('pointercancel', onPointerUp);
            sliderEl.addEventListener('pointerleave', onPointerUp);
        });
    </script>
</div>
