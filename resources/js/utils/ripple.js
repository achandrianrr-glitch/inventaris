export function attachRipple(el) {
    if (!el) return;

    el.classList.add("ripple");

    el.addEventListener("pointerdown", (e) => {
        const rect = el.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        el.style.setProperty("--rx", `${x}px`);
        el.style.setProperty("--ry", `${y}px`);
    });
}
