jQuery($ => {
	const condEl = [...document.querySelectorAll('[data-shown-cond]')];

	for (const input of condEl) {
		const conditions = JSON.parse(input.dataset.shownCond);
		const typeInput = input.parentElement.querySelector('[name$="[type]"]');

		typeInput.addEventListener('change', () => {
			input.style.display = conditions.includes(typeInput.value) ? '' : 'none';
		});

		input.style.display = conditions.includes(typeInput.value) ? '' : 'none';
	}
	
	const listInputs = [...document.querySelectorAll('[data-is-list]')];
	
	for (const listInput of listInputs) {
		const container = listInput.parentElement;
		
		const options = JSON.parse(listInput.dataset.options || '{}');
		const entries = listInput.value ? listInput.value.split(',') : [];
		const entryCnt = document.createElement('div');
		
		const addCnt = document.createElement('div');
		const addInput = document.createElement('input');
		const addBtn = document.createElement('button');	
		const dataset = document.createElement('datalist');
		
		const datasetID = 'list-' + Math.random();
		const optionEls = [];
		
		function processEntryListChange() {
			listInput.value = [...listInput.parentElement.getElementsByClassName('list-entry')]
				.map(entry => entry.dataset.value)
				.join(',');
			
			updateDatalist();
		}
		
		function addEntry(value) {
			const entryEl = document.createElement('div');
			const delBtn = document.createElement('button');
			
			entryEl.dataset.value = value;
			entryEl.className = 'list-entry';
			
			delBtn.type = 'button';
			delBtn.innerHTML = '&times;';
			delBtn.addEventListener('click', () => {
				entryEl.remove();
				processEntryListChange();
			});
			
			entryEl.append(options[value] || value, ' ', delBtn);
			entryCnt.append(entryEl);
		}
		
		
		function updateDatalist() {
			const entries = listInput.value.split(',');

			for (const optionEl of optionEls) {
				if (entries.includes(optionEl.value)) {
					optionEl.setAttribute('disabled', '');
				} else {
					optionEl.removeAttribute('disabled', '');
				}
			}
		}
		
		listInput.style.display = 'none';
		document.body.classList.add('js-lists');
		
		addInput.type = 'text';
		
		addInput.addEventListener('keydown', e => {	
			if (e.key !== 'Enter') return;
			
			e.preventDefault();
			addBtn.click();
		});
		
		addBtn.type = 'button';
		addBtn.textContent = 'Add';
		
		addBtn.addEventListener('click', () => {
			if (!addInput.value) return;

			addEntry(addInput.value);
			
			listInput.value += (listInput.value ? ',' : '') + addInput.value;
			updateDatalist();
			
			addInput.value = '';
		});
		
		addCnt.className = 'add-cnt';
		addCnt.append(addInput, addBtn);
		container.append(entryCnt, addCnt);
		
		$(entryCnt).sortable({
			deactivate: processEntryListChange
		});
		
		for (const entry of entries) {
			addEntry(entry);
		}

		dataset.id = datasetID;
		addInput.setAttribute('list', datasetID);

		for (const id in options) {
			const optionEl = document.createElement('option');
			
			optionEl.value = id;
			optionEl.textContent = options[id];

			dataset.append(optionEl);
			optionEls.push(optionEl);
		}

		updateDatalist();
		addInput.after(dataset);
	}

	const deleteInputs = [...document.querySelectorAll('[name$="[deleted]"]')];

	for (const input of deleteInputs) {
		input.addEventListener('change', () => {
			input.closest('.field-cnt').classList.toggle('deleted', input.checked);
		});
	}

	function setFieldCntIndex(fieldCnt, newI) {
		const namedInputs = [
			...fieldCnt.querySelectorAll('[name^="cffu_fields"]')
		];

		fieldCnt.querySelector('.header-number').textContent = newI + 1;

		for (const namedInput of namedInputs) {
			namedInput.name = namedInput.name.replace(
				/^(cffu_fields)\[\d\]/,
				(_, name) => name + '[' + newI + ']'
			);
		}
	}

	$('#fields').sortable({
		deactivate: () => {
			const fieldCnts = [...document.querySelectorAll('.field-cnt')];
			
			let newI = 0;
			
			for (const fieldCnt of fieldCnts) {
				setFieldCntIndex(fieldCnt, newI);			
				newI++;
			}
		}
	});

	const fieldCnts = document.querySelectorAll('.field-cnt');

	for (const fieldCnt of fieldCnts) {
		const header = fieldCnt.querySelector('.header');
		const content = fieldCnt.querySelector('.field');

		header.addEventListener('click', () => {
			$(content).toggle(300);
		});

		content.style.display = 'none';
	}
});