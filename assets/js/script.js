// Basic site JS: mobile nav toggle and small helpers
document.addEventListener('DOMContentLoaded', function () {
	console.log('Site script loaded');

	var navToggle = document.getElementById('nav-toggle');
	var navMenu = document.getElementById('nav-menu');
	var openIcon = document.getElementById('nav-open-icon');
	var closeIcon = document.getElementById('nav-close-icon');

	if (navToggle && navMenu) {
		navToggle.addEventListener('click', function () {
			var isHidden = navMenu.classList.contains('hidden');
			if (isHidden) {
				navMenu.classList.remove('hidden');
				navToggle.setAttribute('aria-expanded', 'true');
				if (openIcon) openIcon.classList.add('hidden');
				if (closeIcon) closeIcon.classList.remove('hidden');
			} else {
				navMenu.classList.add('hidden');
				navToggle.setAttribute('aria-expanded', 'false');
				if (openIcon) openIcon.classList.remove('hidden');
				if (closeIcon) closeIcon.classList.add('hidden');
			}
		});
	}
	// Live preview for news image input (if present)
	var newsFile = document.getElementById('news-image-file');
	var newsPreview = document.getElementById('news-image-preview');
	var newsPreviewImg = document.getElementById('news-image-preview-img');
	if (newsFile) {
		newsFile.addEventListener('change', function (e) {
			var f = newsFile.files && newsFile.files[0];
			if (f && f.type.indexOf('image/') === 0) {
				var url = URL.createObjectURL(f);
				if (newsPreviewImg) newsPreviewImg.src = url;
				if (newsPreview) newsPreview.style.display = 'block';
			} else {
				if (newsPreviewImg) newsPreviewImg.src = '';
				if (newsPreview) newsPreview.style.display = 'none';
			}
		});
	}

	// Live preview for docs image input (if present)
	var docsFile = document.getElementById('docs-image-file');
	var docsPreview = document.getElementById('docs-image-preview');
	var docsPreviewImg = document.getElementById('docs-image-preview-img');
	if (docsFile) {
		docsFile.addEventListener('change', function (e) {
			var f = docsFile.files && docsFile.files[0];
			if (f && f.type.indexOf('image/') === 0) {
				var url = URL.createObjectURL(f);
				if (docsPreviewImg) docsPreviewImg.src = url;
				if (docsPreview) docsPreview.style.display = 'block';
			} else {
				if (docsPreviewImg) docsPreviewImg.src = '';
				if (docsPreview) docsPreview.style.display = 'none';
			}
		});
	}

	// react to position selects (news/docs)
	var newsPos = document.getElementById('news-image-position');
	if (newsPos && newsPreviewImg) {
		newsPos.addEventListener('change', function () {
			var v = newsPos.value || 'center';
			if (newsPreviewImg) newsPreviewImg.style.objectPosition = v;
		});
	}
	var docsPos = document.getElementById('docs-image-position');
	if (docsPos && docsPreviewImg) {
		docsPos.addEventListener('change', function () {
			var v = docsPos.value || 'center';
			if (docsPreviewImg) docsPreviewImg.style.objectPosition = v;
		});
	}

	// Focal point editor helper
	function setupFocal(editorId, imgId, handleId, inputXId, inputYId) {
		var editor = document.getElementById(editorId);
		var img = document.getElementById(imgId);
		var handle = document.getElementById(handleId);
		var inputX = document.getElementById(inputXId);
		var inputY = document.getElementById(inputYId);
		if (!editor || !img || !handle || !inputX || !inputY) return;

		function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }
		function setPos(x, y) {
			x = clamp(x, 0, 100);
			y = clamp(y, 0, 100);
			handle.style.left = x + '%';
			handle.style.top = y + '%';
			img.style.objectPosition = x + '% ' + y + '%';
			inputX.value = Math.round(x*10)/10;
			inputY.value = Math.round(y*10)/10;
		}

		function clientToPercent(clientX, clientY) {
			var rect = editor.getBoundingClientRect();
			var x = (clientX - rect.left) / rect.width * 100;
			var y = (clientY - rect.top) / rect.height * 100;
			return [x, y];
		}

		// click on editor to move focus
		editor.addEventListener('click', function (e) {
			if (e.target === handle) return;
			var p = clientToPercent(e.clientX, e.clientY);
			setPos(p[0], p[1]);
		});

		// dragging the handle
		var dragging = false;
		handle.addEventListener('pointerdown', function (e) {
			dragging = true;
			handle.setPointerCapture(e.pointerId);
			handle.style.cursor = 'grabbing';
		});
		handle.addEventListener('pointermove', function (e) {
			if (!dragging) return;
			var p = clientToPercent(e.clientX, e.clientY);
			setPos(p[0], p[1]);
		});
		handle.addEventListener('pointerup', function (e) {
			dragging = false;
			try { handle.releasePointerCapture(e.pointerId); } catch (err) {}
			handle.style.cursor = 'grab';
		});

		// Keep focal image in sync when preview image changes (object URL / file select)
		var previewImg = document.getElementById(imgId.replace('-focal','-preview')) || null;
		if (previewImg) {
			previewImg.addEventListener('load', function () {
				img.src = previewImg.src;
			});
			// if preview already has src, copy it
			if (previewImg.src) img.src = previewImg.src;
		}
	}

	// initialize focal editors for news and docs
	setupFocal('news-focal-editor','news-focal-img','news-focal-handle','news-image-focus-x','news-image-focus-y');
	setupFocal('docs-focal-editor','docs-focal-img','docs-focal-handle','docs-image-focus-x','docs-image-focus-y');
});
