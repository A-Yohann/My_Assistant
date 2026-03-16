// Script de calcul des totaux pour la génération de devis
document.addEventListener('DOMContentLoaded', function() {
	function updateTotals() {
		let totalHT = 0;
		document.querySelectorAll('#articlesTable tbody tr').forEach(function(row) {
			const qty = parseFloat(row.querySelector('.article-qty').value) || 0;
			const price = parseFloat(row.querySelector('.article-price').value) || 0;
			const lineTotal = qty * price;
			row.querySelector('.article-total').textContent = lineTotal.toFixed(2) + ' €';
			totalHT += lineTotal;
		});
		const totalHTSpan = document.getElementById('totalHT');
		const totalTVASpan = document.getElementById('totalTVA');
		const totalTTCSpan = document.getElementById('totalTTC');
		if (totalHTSpan && totalTVASpan && totalTTCSpan) {
			totalHTSpan.textContent = totalHT.toFixed(2) + ' €';
			const tva = totalHT * 0.2;
			totalTVASpan.textContent = tva.toFixed(2) + ' €';
			totalTTCSpan.textContent = (totalHT + tva).toFixed(2) + ' €';
		}
	}

	document.querySelectorAll('.article-qty, .article-price').forEach(function(input) {
		input.addEventListener('input', updateTotals);
	});

	updateTotals();
});
/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';


