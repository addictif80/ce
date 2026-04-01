<?php
$pageTitle = 'Calculateur de Retraits';
require_once __DIR__ . '/../../templates/header.php';
?>

<style>
    .ret-columns {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }
    .ret-column {
        background: #fff;
        border: 1px solid #e0e0e0;
        padding: 15px;
    }
    .ret-column h5 {
        color: var(--ce-primary, #dc0032);
        font-size: 16px;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 3px solid var(--ce-primary, #dc0032);
        font-weight: 700;
    }
    .ret-item {
        display: grid;
        grid-template-columns: 1fr 70px 90px;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
        padding: 8px;
        background: #f9f9f9;
        border-left: 3px solid var(--ce-primary, #dc0032);
        transition: background 0.2s;
    }
    .ret-item:hover {
        background: #fff5f7;
    }
    .ret-item label {
        font-weight: 600;
        color: #333;
        font-size: 13px;
    }
    .ret-item input {
        width: 100%;
        padding: 6px;
        border: 1px solid #ccc;
        font-size: 14px;
        text-align: center;
    }
    .ret-item input:focus {
        outline: none;
        border-color: var(--ce-primary, #dc0032);
    }
    .ret-item .ret-val {
        text-align: right;
        font-weight: bold;
        color: var(--ce-primary, #dc0032);
        font-size: 14px;
    }
    .ret-subtotal {
        margin-top: 15px;
        padding: 12px;
        background: var(--ce-primary, #dc0032);
        color: white;
        font-size: 16px;
        font-weight: bold;
        text-align: center;
    }
    .ret-total {
        background: #009640;
        padding: 20px;
        text-align: center;
        color: white;
    }
    .ret-total h4 {
        color: white;
        margin-bottom: 5px;
    }
    .ret-total-amount {
        font-size: 42px;
        font-weight: bold;
    }
    @media (max-width: 1200px) {
        .ret-columns { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 768px) {
        .ret-columns { grid-template-columns: 1fr; }
        .ret-item { grid-template-columns: 1fr 60px 80px; }
    }
</style>

<div class="ret-columns">
    <!-- Billets -->
    <div class="ret-column">
        <h5><i class="fas fa-money-bill-wave"></i> Billets</h5>
        <div class="ret-item">
            <label>Billet de 50 &euro;</label>
            <input type="number" id="billet50" min="0" value="0" oninput="calcRetraits()">
            <div class="ret-val" id="val_billet50">0,00 &euro;</div>
        </div>
        <div class="ret-item">
            <label>Billet de 20 &euro;</label>
            <input type="number" id="billet20" min="0" value="0" oninput="calcRetraits()">
            <div class="ret-val" id="val_billet20">0,00 &euro;</div>
        </div>
        <div class="ret-item">
            <label>Billet de 10 &euro;</label>
            <input type="number" id="billet10" min="0" value="0" oninput="calcRetraits()">
            <div class="ret-val" id="val_billet10">0,00 &euro;</div>
        </div>
        <div class="ret-item">
            <label>Billet de 5 &euro;</label>
            <input type="number" id="billet5" min="0" value="0" oninput="calcRetraits()">
            <div class="ret-val" id="val_billet5">0,00 &euro;</div>
        </div>
        <div class="ret-subtotal" id="total_billets">Total Billets : 0,00 &euro;</div>
    </div>

    <!-- Rouleaux -->
    <div class="ret-column">
        <h5><i class="fas fa-coins"></i> Rouleaux</h5>
        <div class="ret-item">
            <label>Rouleau de 2 &euro; (50 &euro;)</label>
            <input type="number" id="rouleau2" min="0" value="0" oninput="calcRetraits()">
            <div class="ret-val" id="val_rouleau2">0,00 &euro;</div>
        </div>
        <div class="ret-item">
            <label>Rouleau de 1 &euro; (25 &euro;)</label>
            <input type="number" id="rouleau1" min="0" value="0" oninput="calcRetraits()">
            <div class="ret-val" id="val_rouleau1">0,00 &euro;</div>
        </div>
        <div class="ret-item">
            <label>Rouleau de 0,50 &euro; (20 &euro;)</label>
            <input type="number" id="rouleau050" min="0" value="0" oninput="calcRetraits()">
            <div class="ret-val" id="val_rouleau050">0,00 &euro;</div>
        </div>
        <div class="ret-item">
            <label>Rouleau de 0,20 &euro; (8 &euro;)</label>
            <input type="number" id="rouleau020" min="0" value="0" oninput="calcRetraits()">
            <div class="ret-val" id="val_rouleau020">0,00 &euro;</div>
        </div>
        <div class="ret-item">
            <label>Rouleau de 0,10 &euro; (4 &euro;)</label>
            <input type="number" id="rouleau010" min="0" value="0" oninput="calcRetraits()">
            <div class="ret-val" id="val_rouleau010">0,00 &euro;</div>
        </div>
        <div class="ret-item">
            <label>Rouleau de 0,05 &euro; (2,50 &euro;)</label>
            <input type="number" id="rouleau005" min="0" value="0" oninput="calcRetraits()">
            <div class="ret-val" id="val_rouleau005">0,00 &euro;</div>
        </div>
        <div class="ret-item">
            <label>Rouleau de 0,02 &euro; (1 &euro;)</label>
            <input type="number" id="rouleau002" min="0" value="0" oninput="calcRetraits()">
            <div class="ret-val" id="val_rouleau002">0,00 &euro;</div>
        </div>
        <div class="ret-item">
            <label>Rouleau de 0,01 &euro; (0,50 &euro;)</label>
            <input type="number" id="rouleau001" min="0" value="0" oninput="calcRetraits()">
            <div class="ret-val" id="val_rouleau001">0,00 &euro;</div>
        </div>
        <div class="ret-subtotal" id="total_rouleaux">Total Rouleaux : 0,00 &euro;</div>
    </div>

    <!-- Pièces -->
    <div class="ret-column">
        <h5><i class="fas fa-circle"></i> Pi&egrave;ces &agrave; l'unit&eacute;</h5>
        <div class="ret-item">
            <label>Pi&egrave;ce de 2 &euro;</label>
            <input type="number" id="piece2" min="0" value="0" oninput="calcRetraits()">
            <div class="ret-val" id="val_piece2">0,00 &euro;</div>
        </div>
        <div class="ret-item">
            <label>Pi&egrave;ce de 1 &euro;</label>
            <input type="number" id="piece1" min="0" value="0" oninput="calcRetraits()">
            <div class="ret-val" id="val_piece1">0,00 &euro;</div>
        </div>
        <div class="ret-item">
            <label>Pi&egrave;ce de 0,50 &euro;</label>
            <input type="number" id="piece050" min="0" value="0" oninput="calcRetraits()">
            <div class="ret-val" id="val_piece050">0,00 &euro;</div>
        </div>
        <div class="ret-item">
            <label>Pi&egrave;ce de 0,20 &euro;</label>
            <input type="number" id="piece020" min="0" value="0" oninput="calcRetraits()">
            <div class="ret-val" id="val_piece020">0,00 &euro;</div>
        </div>
        <div class="ret-item">
            <label>Pi&egrave;ce de 0,10 &euro;</label>
            <input type="number" id="piece010" min="0" value="0" oninput="calcRetraits()">
            <div class="ret-val" id="val_piece010">0,00 &euro;</div>
        </div>
        <div class="ret-item">
            <label>Pi&egrave;ce de 0,05 &euro;</label>
            <input type="number" id="piece005" min="0" value="0" oninput="calcRetraits()">
            <div class="ret-val" id="val_piece005">0,00 &euro;</div>
        </div>
        <div class="ret-item">
            <label>Pi&egrave;ce de 0,02 &euro;</label>
            <input type="number" id="piece002" min="0" value="0" oninput="calcRetraits()">
            <div class="ret-val" id="val_piece002">0,00 &euro;</div>
        </div>
        <div class="ret-item">
            <label>Pi&egrave;ce de 0,01 &euro;</label>
            <input type="number" id="piece001" min="0" value="0" oninput="calcRetraits()">
            <div class="ret-val" id="val_piece001">0,00 &euro;</div>
        </div>
        <div class="ret-subtotal" id="total_pieces">Total Pi&egrave;ces : 0,00 &euro;</div>
    </div>
</div>

<!-- Total général -->
<div class="ret-total">
    <h4>MONTANT TOTAL DU RETRAIT</h4>
    <div class="ret-total-amount" id="total_general">0,00 &euro;</div>
</div>

<script>
function calcRetraits() {
    const items = [
        // [id, multiplicateur, valId]
        // Billets
        ['billet50', 50], ['billet20', 20], ['billet10', 10], ['billet5', 5],
        // Rouleaux
        ['rouleau2', 50], ['rouleau1', 25], ['rouleau050', 20], ['rouleau020', 8],
        ['rouleau010', 4], ['rouleau005', 2.5], ['rouleau002', 1], ['rouleau001', 0.5],
        // Pièces
        ['piece2', 2], ['piece1', 1], ['piece050', 0.5], ['piece020', 0.2],
        ['piece010', 0.1], ['piece005', 0.05], ['piece002', 0.02], ['piece001', 0.01]
    ];

    let totalBillets = 0, totalRouleaux = 0, totalPieces = 0;
    const fmt = v => v.toFixed(2).replace('.', ',') + ' \u20ac';

    items.forEach(([id, mult]) => {
        const qty = parseFloat(document.getElementById(id).value) || 0;
        const val = qty * mult;
        document.getElementById('val_' + id).textContent = fmt(val);

        if (id.startsWith('billet')) totalBillets += val;
        else if (id.startsWith('rouleau')) totalRouleaux += val;
        else totalPieces += val;
    });

    document.getElementById('total_billets').textContent = 'Total Billets : ' + fmt(totalBillets);
    document.getElementById('total_rouleaux').textContent = 'Total Rouleaux : ' + fmt(totalRouleaux);
    document.getElementById('total_pieces').textContent = 'Total Pi\u00e8ces : ' + fmt(totalPieces);
    document.getElementById('total_general').textContent = fmt(totalBillets + totalRouleaux + totalPieces);
}
calcRetraits();
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
