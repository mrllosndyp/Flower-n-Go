document.addEventListener('DOMContentLoaded', () => {

  // steam pulse
  const steam = document.querySelector('.steam-effect');
  setInterval(() => {
    steam.style.opacity = Math.random() * 0.4 + 0.3;
  }, 2000);

  // reveal animation
  const cards = document.querySelectorAll('.product-card');

  const observer = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.style.opacity = 1;
        e.target.style.transform = 'translateY(0)';
      }
    });
  });

  cards.forEach(card => {
    card.style.opacity = 0;
    card.style.transform = 'translateY(40px)';
    card.style.transition = '0.6s ease';
    observer.observe(card);
  });

});

// Footer Fade-in
const footer = document.querySelector('footer');
footer.style.opacity = 0;
footer.style.transform = 'translateY(40px)';
footer.style.transition = '0.7s ease';

const footerObserver = new IntersectionObserver(entries => {
  if(entries[0].isIntersecting){
    footer.style.opacity = 1;
    footer.style.transform = 'translateY(0)';
  }
},{threshold:0.2});

footerObserver.observe(footer);

// Newsletter Submit
document.getElementById("newsletterForm").addEventListener("submit", function(e){
  e.preventDefault();
  alert("✅ Subscribed successfully!");
  this.reset();
});

// Theme Toggle
const toggle = document.querySelector(".theme-toggle");
toggle.addEventListener("click", ()=>{
  document.body.classList.toggle("light-mode");

  toggle.textContent =
    document.body.classList.contains("light-mode")
    ? "🌞 Light Mode"
    : "🌙 Dark Mode";
});
