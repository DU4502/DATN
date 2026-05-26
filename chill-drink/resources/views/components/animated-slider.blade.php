@props([
    'beverages' => [
        [
            'name' => 'MATCHA LATTE',
            'title' => 'MACCHIATO, A NEW PRODUCT',
            'price' => '85.000₫',
            'image' => 'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?auto=format&fit=crop&q=80&w=800',
            'bg' => '#5d9c59',
            'desc' => 'Hương vị trà xanh Nhật Bản thượng hạng hòa quyện cùng sữa tươi béo ngậy. Một sự lựa chọn hoàn hảo cho những ai yêu thích sự thanh khiết và tươi mới.'
        ],
        [
            'name' => 'STRAWBERRY SHAKE',
            'title' => 'BERRY BLAST, FRESH ENERGY',
            'price' => '75.000₫',
            'image' => 'https://images.unsplash.com/photo-1543362906-acfc16c67564?auto=format&fit=crop&q=80&w=800', 
            'bg' => '#df2e38',
            'desc' => 'Dâu tây tươi Đà Lạt thơm mọng kết hợp với kem vani tạo nên sự tươi mát khó cưỡng cho ngày hè năng động và tràn đầy sức sống.'
        ],
        [
            'name' => 'COLD BREW COFFEE',
            'title' => 'INTENSE BREW, PURE TASTE',
            'price' => '65.000₫',
            'image' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&q=80&w=800',
            'bg' => '#322c2b',
            'desc' => 'Cà phê ủ lạnh 12 giờ cho vị thanh khiết, ít đắng và đượm hương trái cây tự nhiên từ những hạt cà phê Arabica được tuyển chọn kỹ lưỡng.'
        ],
        [
            'name' => 'MANGO TROPIC',
            'title' => 'SUMMER VIBE, EXOTIC MIX',
            'price' => '90.000₫',
            'image' => 'https://images.unsplash.com/photo-1623065426202-18a0ad12e902?auto=format&fit=crop&q=80&w=800',
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
            background: var(--drink-primary, #008b7a);
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

        /* Navigation Arrows */
        .arrows {
            position: absolute;
            bottom: 40px;
            left: 45%;
            transform: translateX(-50%);
            display: flex;
            gap: 15px;
            z-index: 100;
        }

        .arrows button {
            width: 45px;
            height: 45px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
            backdrop-filter: blur(10px);
        }

        .arrows button:hover {
            background: rgba(255,255,255,0.2);
            border-color: #fff;
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
            <div class="item" style="--bg: {{ $item['bg'] }}">
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

    <div class="arrows">
        <button id="prevBtn"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg></button>
        <button id="nextBtn"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg></button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const nextBtn        = document.getElementById('nextBtn');
            const prevBtn        = document.getElementById('prevBtn');
            const list           = document.querySelector('#mainSlider .list');
            const sliderEl       = document.getElementById('mainSlider');
            const bgBase         = document.getElementById('sliderBg');
            const bgIncoming     = document.getElementById('sliderBgIncoming');

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

            nextBtn.onclick = () => moveSlider('next');
            prevBtn.onclick = () => moveSlider('prev');

            // Autoplay
            let autoplayInterval = setInterval(() => moveSlider('next'), 6000);
            const resetAutoplay = () => {
                clearInterval(autoplayInterval);
                autoplayInterval = setInterval(() => moveSlider('next'), 6000);
            };
            nextBtn.addEventListener('click', resetAutoplay);
            prevBtn.addEventListener('click', resetAutoplay);
        });
    </script>
</div>
