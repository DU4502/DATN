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
            'desc' => 'Trà sữa đậm đà hòa quyện với sữa tươi, nổi bật trên nền trắng tinh khôi và chữ đỏ rực.'
        ],
        [
            'name' => 'CÀ PHÊ Ủ LẠNH',
            'title' => 'CÀ PHÊ Ủ LẠNH ĐẬM ĐÀ, SANG CHẢNH',
            'price' => '65.000₫',
            'image' => '/images/cafe.png',
            'bg' => '#322c2b',
            'desc' => 'Cà phê ủ lạnh 12 giờ cho vị thanh khiết, ít đắng và đượm hương trái cây tự nhiên từ những hạt cà phê Arabica đặc sản.'
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
        .slider {
            width: 100%;
            height: 100vh;
            min-height: 600px;
            overflow: hidden;
            position: relative;
            background-color: #111;
            font-family: var(--font-sans);
            color: #fff;
        }

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
            
            --transition-speed: 0.9s;
        }

        .item.item-white .content { color: #111; }
        .item.item-white .content h1,
        .item.item-white .content h2,
        .item.item-white .content .price,
        .item.item-white .content .description { color: #111; text-shadow: none; }
        .item.item-white .btn-order { background: #df2e38; color: #fff; }
        .item.item-white .btn-suggest { color: #111; border-color: rgba(0,0,0,0.2); background: rgba(0,0,0,0.04); }
        .item.item-white .progress-track { background: rgba(0,0,0,0.1); }
        .item.item-white .progress-fill { background: #111; }
        .item.item-white .dot { border-color: rgba(0,0,0,0.3); }
        .item.item-white .dot.active { background: #111; border-color: #111; }

        .list { width: 100%; height: 100%; position: relative; z-index: 2;}

        .item {
            position: absolute; inset: 0;
            display: grid; grid-template-columns: 45% 55%;
            transition: 0.5s;
        }

        /* Particles */
        .particles {
            position: absolute; inset: 0; z-index: 1; pointer-events: none;
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(255,255,255,0.1) 0, rgba(255,255,255,0) 2px),
                radial-gradient(circle at 70% 60%, rgba(255,255,255,0.15) 0, rgba(255,255,255,0) 3px),
                radial-gradient(circle at 40% 80%, rgba(255,255,255,0.08) 0, rgba(255,255,255,0) 2px),
                radial-gradient(circle at 80% 20%, rgba(255,255,255,0.12) 0, rgba(255,255,255,0) 2px);
            background-size: 200px 200px;
            animation: drift 40s linear infinite;
        }

        @keyframes drift {
            from { background-position: 0 0; }
            to { background-position: -400px -400px; }
        }

        /* Two-layer JS-driven background system */
        .slider-bg, .slider-bg-incoming {
            position: absolute; inset: 0; z-index: 0; pointer-events: none;
        }
        .slider-bg {
            background-color: #5d9c59; transition: none;
        }
        .slider-bg-incoming {
            clip-path: circle(0% at 50% 50%); background-color: transparent;
        }
        .slider-bg-incoming.revealing {
            animation: bgReveal 1s cubic-bezier(0.7, 0, 0.3, 1) forwards;
        }
        @keyframes bgReveal {
            from { clip-path: circle(0% at 50% 50%); }
            to   { clip-path: circle(150% at 50% 50%); }
        }

        /* Content section */
        .content {
            padding: 0 10%; display: flex; flex-direction: column; justify-content: center;
            z-index: 15; opacity: 0; pointer-events: none; transition: 0.5s;
        }
        .item:nth-child(2) .content { opacity: 1; pointer-events: auto; }

        .content h1 {
            font-family: 'Clash Display', 'Inter', sans-serif;
            font-size: clamp(3rem, 6vw, 5.5rem);
            line-height: 1; margin: 0; text-transform: uppercase;
            font-weight: 800; letter-spacing: -0.02em;
            transform: translateY(40px); filter: blur(15px);
            transition: var(--transition-speed) cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        .content h2 {
            font-family: 'Inter', sans-serif;
            font-size: clamp(1.2rem, 2.5vw, 2rem);
            font-weight: 500; margin: 15px 0; text-transform: uppercase;
            letter-spacing: 0.05em;
            transform: translateY(40px); filter: blur(15px);
            transition: var(--transition-speed) 0.1s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        .content .price {
            font-size: 2.2rem; font-weight: 800; margin: 10px 0 20px;
            transform: translateY(40px); filter: blur(15px);
            transition: var(--transition-speed) 0.15s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        .content .description {
            max-width: 450px; line-height: 1.7; margin-bottom: 40px;
            font-size: 1.05rem; font-weight: 400; opacity: 0.9;
            transform: translateY(40px); filter: blur(15px);
            transition: var(--transition-speed) 0.2s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        .content .btn-group {
            display: flex; gap: 15px; flex-wrap: wrap;
            transform: translateY(40px); filter: blur(15px);
            transition: var(--transition-speed) 0.25s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        .content .btn-order, .content .btn-suggest {
            padding: 14px 32px; border-radius: 40px;
            font-weight: 700; font-size: 0.95rem; text-decoration: none;
            display: inline-flex; align-items: center; justify-content: center;
            transition: all 0.3s ease;
        }

        .content .btn-order {
            background: var(--btn-bg, #00BFA6); color: #fff;
            border: 2px solid transparent; box-shadow: 0 10px 24px rgba(0,0,0,0.2);
        }
        .content .btn-order:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(0,0,0,0.3); }

        .content .btn-suggest {
            background: transparent; color: #fff;
            border: 2px solid rgba(255,255,255,0.5);
        }
        .content .btn-suggest:hover {
            background: rgba(255,255,255,0.1); border-color: #fff; transform: translateY(-3px);
        }

        .item:nth-child(2) .content h1,
        .item:nth-child(2) .content h2,
        .item:nth-child(2) .content .price,
        .item:nth-child(2) .content .description,
        .item:nth-child(2) .content .btn-group {
            transform: translateY(0); filter: blur(0);
        }

        /* Image Gallery section */
        .image-gallery {
            position: relative; display: flex; align-items: center; justify-content: center;
            overflow: visible; z-index: 20;
        }

        .product-img {
            width: 500px; aspect-ratio: 1; object-fit: contain; position: absolute;
            transition: var(--transition-speed) cubic-bezier(0.25, 1, 0.3, 1);
            filter: drop-shadow(0 40px 60px rgba(0,0,0,0.4));
        }

        .item:nth-child(1) .product-img {
            transform: var(--item1-transform); filter: var(--item1-filter);
            z-index: var(--item1-zIndex); opacity: var(--item1-opacity);
        }

        .item:nth-child(2) .product-img {
            transform: var(--item2-transform); filter: var(--item2-filter);
            z-index: var(--item2-zIndex); opacity: var(--item2-opacity);
        }

        .item:nth-child(3) .product-img {
            transform: var(--item3-transform); filter: var(--item3-filter);
            z-index: var(--item3-zIndex); opacity: var(--item3-opacity);
        }

        .item:nth-child(4) .product-img {
            transform: var(--item4-transform); filter: var(--item4-filter);
            z-index: var(--item4-zIndex); opacity: var(--item4-opacity);
        }

        .item:nth-child(n+5) .product-img {
            transform: var(--item5-transform); filter: var(--item5-filter);
            z-index: var(--item5-zIndex); opacity: var(--item5-opacity);
        }

        /* Slider Controls */
        .slider-controls {
            position: absolute; bottom: 40px; left: 50%; transform: translateX(-50%); z-index: 30;
            display: flex; align-items: center; justify-content: center; gap: 40px; width: 90%; max-width: 500px;
        }

        .progress-bar {
            flex-grow: 1; height: 3px; position: relative;
        }
        .progress-track {
            position: absolute; inset: 0; background: rgba(255,255,255,0.2); border-radius: 2px;
        }
        .progress-fill {
            position: absolute; left: 0; top: 0; bottom: 0; width: 0%;
            background: #fff; border-radius: 2px;
        }
        
        .progress-fill.animating {
            transition: width 6s linear;
        }

        .dots { display: flex; gap: 8px; }
        .dot {
            width: 10px; height: 10px; border-radius: 50%;
            border: 1.5px solid rgba(255,255,255,0.5); cursor: pointer;
            transition: all 0.3s ease;
        }
        .dot.active { background: #fff; border-color: #fff; transform: scale(1.2); }

        /* Responsive Design */
        @media screen and (max-width: 1023px) {
            .product-img { width: 400px; }
            .content h1 { font-size: 4rem; }
            .content h2 { font-size: 1.6rem; }
            .slider-controls { width: 90%; left: 50%; transform: translateX(-50%); bottom: 40px; justify-content: center; max-width: none;}
            .progress-bar { display: none; }
        }

        @media screen and (max-width: 767px) {
            .item { grid-template-columns: 100%; grid-template-rows: 45% 55%; }
            .content { order: 2; padding: 20px; text-align: center; align-items: center; }
            .image-gallery { order: 1; }
            .product-img { width: 280px; }
            .content h1 { font-size: 3rem; }
            .content h2 { font-size: 1.3rem; }
            .content .description { display: none; }
            .content .btn-group { justify-content: center; }
            .slider-controls { bottom: 20px; }
            
            /* Adjust positions for small screens */
            --item3-transform: translate(35%, 15%) scale(0.6);
            --item4-transform: translate(70%, 25%) scale(0.4);
        }
    </style>

    <div class="slider-bg" id="sliderBg"></div>
    <div class="slider-bg-incoming" id="sliderBgIncoming"></div>
    <div class="particles"></div>

    <div class="list">
        @foreach($beverages as $index => $item)
            <div class="item{{ $item['name'] === 'TRÀ SỮA' ? ' item-white' : '' }}" data-index="{{ $index }}" style="--bg: {{ $item['bg'] }}; --btn-bg: {{ $item['name'] === 'TRÀ SỮA' ? '#df2e38' : $item['bg'] }};">
                <div class="content">
                    <h1>{{ $item['name'] }}</h1>
                    <h2>{{ $item['title'] }}</h2>
                    <p class="price">{{ $item['price'] }}</p>
                    <p class="description">{{ $item['desc'] }}</p>
                    <div class="btn-group">
                        <a href="{{ route('products.index') }}" class="btn-order">Thưởng thức ngay</a>
                        <a href="#featured-products" class="btn-suggest">Gợi ý hôm nay</a>
                    </div>
                </div>
                <div class="image-gallery">
                    <img src="{{ $item['image'] }}" class="product-img" alt="{{ $item['name'] }}">
                </div>
            </div>
        @endforeach
    </div>

    <div class="slider-controls">
        <div class="progress-bar">
            <div class="progress-track"></div>
            <div class="progress-fill" id="progressFill"></div>
        </div>
        <div class="dots" id="sliderDots">
            @foreach($beverages as $index => $item)
                <div class="dot {{ $index === 0 ? 'active' : '' }}" data-dot-index="{{ $index }}"></div>
            @endforeach
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sliderEl   = document.getElementById('mainSlider');
            const list       = document.querySelector('#mainSlider .list');
            const bgBase     = document.getElementById('sliderBg');
            const bgIncoming = document.getElementById('sliderBgIncoming');
            const progressFill = document.getElementById('progressFill');
            const dotsContainer = document.getElementById('sliderDots');
            
            let isAnimating = false;
            let currentIndex = 0;
            const totalItems = document.querySelectorAll('.list .item').length;
            const slideDuration = 6000;

            const getBgColor = (item) => item.style.getPropertyValue('--bg').trim();

            const initItems = list.querySelectorAll('.item');
            if (initItems.length >= 2) {
                bgBase.style.backgroundColor = getBgColor(initItems[1]);
                currentIndex = parseInt(initItems[1].dataset.index);
            }

            const updateDots = (index) => {
                const dots = document.querySelectorAll('.dot');
                dots.forEach(d => d.classList.remove('active'));
                const activeDot = document.querySelector(`.dot[data-dot-index="${index}"]`);
                if(activeDot) activeDot.classList.add('active');
                
                // Cập nhật màu dot/progress theo slide hiện tại nếu slide sáng màu
                const activeItem = list.querySelector('.item:nth-child(2)');
                if (activeItem && activeItem.classList.contains('item-white')) {
                    sliderEl.classList.add('theme-light');
                } else {
                    sliderEl.classList.remove('theme-light');
                }
            };
            
            updateDots(currentIndex);

            const startProgress = () => {
                progressFill.classList.remove('animating');
                progressFill.style.width = '0%';
                
                // Force reflow
                void progressFill.offsetWidth;
                
                progressFill.classList.add('animating');
                progressFill.style.width = '100%';
            };

            const triggerBgReveal = (newColor) => {
                bgIncoming.style.backgroundColor = newColor;
                bgIncoming.classList.remove('revealing');
                void bgIncoming.offsetWidth;
                bgIncoming.classList.add('revealing');

                setTimeout(() => {
                    bgBase.style.backgroundColor = newColor;
                    bgIncoming.classList.remove('revealing');
                    bgIncoming.style.backgroundColor = 'transparent';
                }, 1000);
            };

            const moveSlider = (direction, targetIndex = null) => {
                if (isAnimating) return;
                isAnimating = true;

                const items = list.querySelectorAll('.item');

                if (direction === 'next') {
                    list.appendChild(items[0]);
                } else if (direction === 'prev') {
                    list.prepend(items[items.length - 1]);
                }
                
                const newActive = list.querySelector('.item:nth-child(2)');
                if (newActive) {
                    triggerBgReveal(getBgColor(newActive));
                    currentIndex = parseInt(newActive.dataset.index);
                    updateDots(currentIndex);
                }

                startProgress();

                setTimeout(() => {
                    isAnimating = false;
                }, 900);
            };

            sliderEl.style.touchAction = 'pan-y';

            let autoplayInterval = setInterval(() => moveSlider('next'), slideDuration);
            startProgress();

            const resetAutoplay = () => {
                clearInterval(autoplayInterval);
                autoplayInterval = setInterval(() => moveSlider('next'), slideDuration);
                startProgress();
            };

            // Dot navigation (click to next until finding the right one, simplified as next only for this dom order structure)
            document.querySelectorAll('.dot').forEach(dot => {
                dot.addEventListener('click', (e) => {
                    if (isAnimating) return;
                    const targetIdx = parseInt(e.target.dataset.dotIndex);
                    if (targetIdx === currentIndex) return;
                    
                    // Simple next loop
                    moveSlider('next');
                    resetAutoplay();
                });
            });

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
