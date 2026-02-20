class Carousel {
    constructor() {
        this.slides = document.querySelectorAll('.slide');
        this.dots = document.querySelectorAll('.dot');
        this.currentSlide = 0;
        this.autoplayInterval = null;
        this.autoplayDelay = 5000;
        this.carousel = document.querySelector('.hero-carousel');
        
        this.init();
    }
    
    init() {
        this.createNavArrows();
        
        this.dots.forEach((dot, index) => {
            dot.addEventListener('click', () => this.goToSlide(index));
        });
        
        this.startAutoplay();
        
        this.carousel.addEventListener('mouseenter', () => this.stopAutoplay());
        this.carousel.addEventListener('mouseleave', () => this.startAutoplay());
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') this.prevSlide();
            if (e.key === 'ArrowRight') this.nextSlide();
        });
        
        this.addTouchSupport();
    }
    
    createNavArrows() {
        const prevBtn = document.createElement('div');
        prevBtn.className = 'carousel-nav prev';
        prevBtn.innerHTML = "<i class='bx bx-left-arrow-alt'></i>";
        prevBtn.addEventListener('click', () => this.prevSlide());
        
        const nextBtn = document.createElement('div');
        nextBtn.className = 'carousel-nav next';
        nextBtn.innerHTML = "<i class='bx bx-right-arrow-alt'></i>";
        nextBtn.addEventListener('click', () => this.nextSlide());
        
        this.carousel.appendChild(prevBtn);
        this.carousel.appendChild(nextBtn);
    }
    
    goToSlide(index) {
        this.slides[this.currentSlide].classList.remove('active');
        this.dots[this.currentSlide].classList.remove('active');
        
        this.currentSlide = index;
        
        this.slides[this.currentSlide].classList.add('active');
        this.dots[this.currentSlide].classList.add('active');
        
        this.restartProgressAnimation();
        
        this.stopAutoplay();
        this.startAutoplay();
    }
    
    restartProgressAnimation() {
        const activeDot = this.dots[this.currentSlide];
        void activeDot.offsetWidth;
        activeDot.classList.remove('active');
        void activeDot.offsetWidth;
        activeDot.classList.add('active');
    }
    
    nextSlide() {
        const nextIndex = (this.currentSlide + 1) % this.slides.length;
        this.goToSlide(nextIndex);
    }
    
    prevSlide() {
        const prevIndex = (this.currentSlide - 1 + this.slides.length) % this.slides.length;
        this.goToSlide(prevIndex);
    }
    
    startAutoplay() {
        this.carousel.classList.remove('paused');
        
        this.autoplayInterval = setInterval(() => {
            this.nextSlide();
        }, this.autoplayDelay);
    }
    
    stopAutoplay() {
        this.carousel.classList.add('paused');
        
        if (this.autoplayInterval) {
            clearInterval(this.autoplayInterval);
            this.autoplayInterval = null;
        }
    }
    
    addTouchSupport() {
        let touchStartX = 0;
        let touchEndX = 0;
        
        this.carousel.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        });
        
        this.carousel.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            this.handleSwipe();
        });
        
        const handleSwipe = () => {
            const swipeThreshold = 50;
            const diff = touchStartX - touchEndX;
            
            if (Math.abs(diff) > swipeThreshold) {
                if (diff > 0) {
                    this.nextSlide();
                } else {
                    this.prevSlide();
                }
            }
        };
        
        this.handleSwipe = handleSwipe;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new Carousel();
});