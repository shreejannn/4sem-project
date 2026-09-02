<?php require_once "config/session.php"; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Connect with trusted, verified skilled blue-collar workers near you.">
    <title>BlueCollar-Hire | Find Trusted Skilled Workers</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
</head>

<body>

    <?php $base = ""; include "includes/navbar.php"; ?>

    <main>
        <section class="hero">
            <div class="hero-text">
                <h1>Find Trusted Skilled Workers<br>Near You</h1>
                <p>
                    Connect with experienced plumbers, electricians, carpenters,
                    painters, cleaners, mechanics, and other skilled professionals
                    for your home or business.
                </p>

                <!-- Search Bar (Now uses Category IDs) -->
                <form action="browse.php" method="GET" class="hero-search">
                    <select name="category" aria-label="Select Category" required>
                        <option value="" disabled selected>Select a Service...</option>
                        <option value="1">Plumber</option>
                        <option value="2">Electrician</option>
                        <option value="5">Cleaner</option>
                        <option value="6">Mechanic</option>
                        <option value="3">Carpenter</option>
                    </select>
                    &ensp;
                    <button type="submit" class="btn primary"><i class="fa-solid fa-magnifying-glass"></i>  Search</button>
                </form>
            </div>

            <!-- Slider -->
            <div class="slider-container">
                <div class="slider-wrapper">
                    <!-- Replace text with image tags when you have images -->
                    <div class="slide" style="background-color: #3498db;"><img src="assets/images/electrician.png" alt="Slide 1"></div>
                    <div class="slide" style="background-color: #e74c3c;"><img src="assets/images/gardener.png" alt="Slide 2"></div>
                    <div class="slide" style="background-color: #2ecc71;"><img src="assets/images/plumber.png" alt="Slide 3"></div>
                </div>
              
                <button class="slider-btn prev-btn" aria-label="Previous Slide">&#10094;</button>
                <button class="slider-btn next-btn" aria-label="Next Slide">&#10095;</button>

                <div class="slider-dots">
                    <span class="dot active" data-index="0"></span>
                    <span class="dot" data-index="1"></span>
                    <span class="dot" data-index="2"></span>
                </div>
            </div>
        </section>

        <!-- Popular Categories (Now use Category IDs) -->
        <section class="popular-categories">
            <h2>Popular Services</h2>
            <div class="category-grid">
                <a href="browse.php?category=1" class="category-card">
                    <i class="fa-solid fa-wrench"></i>
                    <span>Plumbing</span>
                </a>
                <a href="browse.php?category=2" class="category-card">
                    <i class="fa-solid fa-plug"></i>
                    <span>Electrical</span>
                </a>
                <a href="browse.php?category=5" class="category-card">
                    <i class="fa-solid fa-broom"></i>
                    <span>Cleaning</span>
                </a>
                <a href="browse.php?category=3" class="category-card">
                    <i class="fa-solid fa-hammer"></i>
                    <span>Carpentry</span>
                </a>
            </div>
        </section>

        <section class="features">
            <h2>Why Choose Our Platform ?</h2><br>
            <div class="cards">
                <div class="card">
                    <i class="fa-solid fa-user-check" aria-hidden="true"></i>
                    <h3>Verified Workers</h3>
                    <p>Browse trusted worker profiles approved by the administrator.</p>
                </div>
                <div class="card">
                    <i class="fa-solid fa-briefcase" aria-hidden="true"></i>
                    <h3>Easy Hiring</h3>
                    <p>Find skilled workers by category and send work requests in minutes.</p>
                </div>
                <div class="card">
                    <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                    <h3>Local Services</h3>
                    <p>Hire skilled professionals available everywhere for everyday jobs.</p>
                </div>
            </div>
        </section>
    </main>

    <?php include "includes/footer.php"; ?>

    <script>
        const wrapper = document.querySelector('.slider-wrapper');
        const sliderContainer = document.querySelector('.slider-container');
        const slides = document.querySelectorAll('.slide');
        const prevBtn = document.querySelector('.prev-btn');
        const nextBtn = document.querySelector('.next-btn');
        const dots = document.querySelectorAll('.dot');

        let currentIndex = 0;
        let autoSlideInterval;

        function showSlide(index) {
            if (index >= slides.length) currentIndex = 0;
            else if (index < 0) currentIndex = slides.length - 1;
            else currentIndex = index;
            
            wrapper.style.transform = `translateX(-${currentIndex * 100}%)`;
            dots.forEach(dot => dot.classList.remove('active'));
            dots[currentIndex].classList.add('active');
        }

        function nextSlide() { showSlide(currentIndex + 1); resetInterval(); }
        function prevSlide() { showSlide(currentIndex - 1); resetInterval(); }

        function startAutoSlide() {
            autoSlideInterval = setInterval(() => { showSlide(currentIndex + 1); }, 3000); 
        }

        function resetInterval() {
            clearInterval(autoSlideInterval);
            startAutoSlide();
        }

        nextBtn.addEventListener('click', nextSlide);
        prevBtn.addEventListener('click', prevSlide);
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => { showSlide(index); resetInterval(); });
        });

        sliderContainer.addEventListener('mouseenter', () => clearInterval(autoSlideInterval));
        sliderContainer.addEventListener('mouseleave', startAutoSlide);

        startAutoSlide();
    </script>
</body>
</html>