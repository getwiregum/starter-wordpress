(() => {
// Vanilla port of WireGum's "gummy" wordmark: each letter of [data-gummy]
// stretches and thins toward the cursor using Mona Sans' variable width axis.
const MIN_WIDTH = 75;
const MAX_WIDTH = 125;
const MIN_WEIGHT = 300;
const MAX_WEIGHT = 760;
const STEP = 7;

const clampWidth = (value) => Math.min(MAX_WIDTH, Math.max(MIN_WIDTH, value));

const weightFor = (width) => {
  const progress = (width - MIN_WIDTH) / (MAX_WIDTH - MIN_WIDTH);

  return MAX_WEIGHT - (MAX_WEIGHT - MIN_WEIGHT) * progress;
};

function gummyLogo() {
  const element = document.querySelector("[data-gummy]");

  if (!element || window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
    return;
  }

  const text = element.textContent.trim();
  element.setAttribute("aria-label", text);
  element.textContent = "";

  const letters = Array.from(text).map((letter) => {
    const span = document.createElement("span");
    span.className = "gummy-letter";
    span.setAttribute("aria-hidden", "true");
    // A plain space would collapse inside the flex container.
    span.textContent = letter === " " ? "\u00A0" : letter;
    element.append(span);

    return span;
  });

  const apply = (span, width) => {
    span.style.setProperty("--gummy-stretch", `${width.toFixed(2)}%`);
    span.style.setProperty("--gummy-weight", weightFor(width).toFixed(2));
  };

  // At rest the letters fall back to the stylesheet defaults, so the wordmark
  // reads as a plain condensed logo until the cursor interacts with it.
  const reset = () => {
    letters.forEach((span) => {
      span.style.removeProperty("--gummy-stretch");
      span.style.removeProperty("--gummy-weight");
    });
  };

  const setPeakFromPointer = (event) => {
    const peakIndex = letters.reduce((closest, span, index) => {
      const closestRect = letters[closest].getBoundingClientRect();
      const rect = span.getBoundingClientRect();
      const closestDistance = Math.abs(event.clientX - (closestRect.left + closestRect.width / 2));
      const distance = Math.abs(event.clientX - (rect.left + rect.width / 2));

      return distance < closestDistance ? index : closest;
    }, 0);

    letters.forEach((span, index) => {
      apply(span, clampWidth(MIN_WIDTH + Math.abs(index - peakIndex) * STEP));
    });
  };

  element.addEventListener("pointerenter", setPeakFromPointer);
  element.addEventListener("pointermove", setPeakFromPointer);
  element.addEventListener("pointerleave", reset);
}

gummyLogo();
})();
