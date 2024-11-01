function onWuunderButtonLoad() {
	let originalShippingMethodArea = document.querySelectorAll('[data-title="Shipping"]');
	if (originalShippingMethodArea) {
		if (originalShippingMethodArea.length) {
			originalShippingMethodArea[0].style.display = "none";
		}
	}
}