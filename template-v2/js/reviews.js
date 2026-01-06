/**
 * Google Reviews Slider
 */
class ReviewsSlider {
    constructor() {
        this.slider = document.getElementById('reviewsSlider');
        this.dotsContainer = document.getElementById('reviewsDots');
        this.prevBtn = document.getElementById('prevReview');
        this.nextBtn = document.getElementById('nextReview');

        if (!this.slider) return;

        this.cards = Array.from(this.slider.querySelectorAll('.review-card'));
        this.currentIndex = 0;
        this.autoplayInterval = null;

        this.init();
    }

    init() {
        // Create dots
        this.createDots();

        // Event listeners
        this.prevBtn?.addEventListener('click', () => this.prev());
        this.nextBtn?.addEventListener('click', () => this.next());

        // Touch/swipe support
        this.initTouchEvents();

        // Auto-scroll on slider scroll
        this.slider.addEventListener('scroll', () => this.updateDotsOnScroll());

        // Start autoplay
        this.startAutoplay();

        // Pause autoplay on hover
        this.slider.addEventListener('mouseenter', () => this.stopAutoplay());
        this.slider.addEventListener('mouseleave', () => this.startAutoplay());
    }

    createDots() {
        this.cards.forEach((_, index) => {
            const dot = document.createElement('div');
            dot.className = `slider-dot ${index === 0 ? 'active' : ''}`;
            dot.addEventListener('click', () => this.goTo(index));
            this.dotsContainer.appendChild(dot);
        });
        this.dots = Array.from(this.dotsContainer.querySelectorAll('.slider-dot'));
    }

    updateDots() {
        this.dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === this.currentIndex);
        });
    }

    updateDotsOnScroll() {
        const scrollLeft = this.slider.scrollLeft;
        const cardWidth = this.cards[0]?.offsetWidth || 0;
        const gap = 30; // Gap between cards
        const newIndex = Math.round(scrollLeft / (cardWidth + gap));

        if (newIndex !== this.currentIndex && newIndex >= 0 && newIndex < this.cards.length) {
            this.currentIndex = newIndex;
            this.updateDots();
        }
    }

    goTo(index) {
        if (index < 0 || index >= this.cards.length) return;

        this.currentIndex = index;
        const card = this.cards[index];
        const scrollLeft = card.offsetLeft - (this.slider.offsetWidth - card.offsetWidth) / 2;

        this.slider.scrollTo({
            left: scrollLeft,
            behavior: 'smooth'
        });

        this.updateDots();
    }

    next() {
        const nextIndex = (this.currentIndex + 1) % this.cards.length;
        this.goTo(nextIndex);
    }

    prev() {
        const prevIndex = (this.currentIndex - 1 + this.cards.length) % this.cards.length;
        this.goTo(prevIndex);
    }

    startAutoplay() {
        this.stopAutoplay();
        this.autoplayInterval = setInterval(() => this.next(), 5000);
    }

    stopAutoplay() {
        if (this.autoplayInterval) {
            clearInterval(this.autoplayInterval);
            this.autoplayInterval = null;
        }
    }

    initTouchEvents() {
        let startX = 0;
        let scrollLeft = 0;

        this.slider.addEventListener('touchstart', (e) => {
            startX = e.touches[0].pageX - this.slider.offsetLeft;
            scrollLeft = this.slider.scrollLeft;
        });

        this.slider.addEventListener('touchmove', (e) => {
            e.preventDefault();
            const x = e.touches[0].pageX - this.slider.offsetLeft;
            const walk = (x - startX) * 2;
            this.slider.scrollLeft = scrollLeft - walk;
        });
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => new ReviewsSlider());
} else {
    new ReviewsSlider();
}
