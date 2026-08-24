(function () {
    const root = document.querySelector(".meds-block");
    if (!root) {
        return;
    }

    const catalog = JSON.parse(root.getAttribute("data-catalog") || "[]");
    const rows = document.getElementById("med-rows");
    const add = document.getElementById("add-med");
    if (!rows || !add) {
        return;
    }

    let index = 0;

    function optionLabel(item) {
        const strength = item.strength ? " · " + item.strength : "";
        return item.name + strength + " (" + item.on_hand + " " + item.unit + ")";
    }

    function addRow() {
        const i = index++;
        const wrap = document.createElement("div");
        wrap.className = "med-row";

        const select = document.createElement("select");
        select.name = "meds[" + i + "][medicine_id]";
        select.required = true;
        const blank = document.createElement("option");
        blank.value = "";
        blank.textContent = "Choose medicine";
        select.appendChild(blank);
        catalog.forEach(function (item) {
            const opt = document.createElement("option");
            opt.value = String(item.id);
            opt.textContent = optionLabel(item);
            opt.dataset.onHand = String(item.on_hand);
            opt.dataset.unit = item.unit;
            select.appendChild(opt);
        });

        const qty = document.createElement("input");
        qty.type = "number";
        qty.min = "0.01";
        qty.step = "0.01";
        qty.required = true;
        qty.name = "meds[" + i + "][quantity]";
        qty.placeholder = "Qty";

        const dose = document.createElement("input");
        dose.name = "meds[" + i + "][dose_instructions]";
        dose.placeholder = "Dose, e.g. 1 tablet 3× daily";

        const hint = document.createElement("p");
        hint.className = "hint";
        hint.textContent = "";

        const remove = document.createElement("button");
        remove.type = "button";
        remove.className = "btn btn-quiet";
        remove.textContent = "Remove";
        remove.addEventListener("click", function () {
            wrap.remove();
        });

        function updateHint() {
            const opt = select.selectedOptions[0];
            if (!opt || !opt.value) {
                hint.textContent = "";
                qty.removeAttribute("max");
                return;
            }
            const onHand = opt.dataset.onHand || "0";
            qty.max = onHand;
            hint.textContent = onHand + " " + (opt.dataset.unit || "") + " on hand";
        }

        select.addEventListener("change", updateHint);
        qty.addEventListener("input", function () {
            const opt = select.selectedOptions[0];
            if (!opt || !opt.value) {
                return;
            }
            if (Number(qty.value) > Number(opt.dataset.onHand || 0)) {
                qty.setCustomValidity("Not enough on hand");
            } else {
                qty.setCustomValidity("");
            }
        });

        wrap.appendChild(select);
        wrap.appendChild(qty);
        wrap.appendChild(dose);
        wrap.appendChild(remove);
        wrap.appendChild(hint);
        rows.appendChild(wrap);
    }

    add.addEventListener("click", addRow);
    if (catalog.length) {
        addRow();
    }
})();
