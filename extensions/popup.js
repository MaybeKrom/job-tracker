const TRACKER_URL = 'http://localhost/job-tracker/extension_save.php';

let extractedData = null;

document.getElementById('extractBtn').addEventListener('click', async () => {
    const btn = document.getElementById('extractBtn');
    const status = document.getElementById('status');

    btn.disabled = true;
    status.className = 'loading';
    status.textContent = '⏳ Reading page...';

    try {
        // Get current tab
        const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });

        // Inject script to grab page text
        const results = await chrome.scripting.executeScript({
            target: { tabId: tab.id },
            func: () => {
                // Try to get main content only (cleaner text)
                const main = document.querySelector('main') || 
                             document.querySelector('article') || 
                             document.querySelector('.job-view-layout') ||
                             document.body;
                return main.innerText.substring(0, 3000); // limit to 3000 chars
            }
        });

        const pageText = results[0].result;
        const pageUrl = tab.url;

        // Detect method from URL
        let method = 'Website';
        if (pageUrl.includes('linkedin.com')) method = 'LinkedIn';

        document.getElementById('methodSelect').value = method;

        status.textContent = '🤖 Claude is extracting details...';

        // Send to our PHP backend for Claude extraction
        const response = await fetch('http://localhost/job-tracker/extension_extract.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ text: pageText, url: pageUrl })
        });

        const data = await response.json();

        if (data.success) {
            extractedData = data.extracted;
            extractedData.source_url = pageUrl;
            document.getElementById('p-url').textContent = pageUrl.length > 40 ? pageUrl.substring(0, 40) + '...' : pageUrl;

            // Show preview
            document.getElementById('p-company').textContent = extractedData.company || 'Not found';
            document.getElementById('p-role').textContent = extractedData.role || 'Not found';
            document.getElementById('p-contact').textContent = extractedData.contact || 'None';
            document.getElementById('p-notes').textContent = extractedData.notes || 'None';

            document.getElementById('preview').style.display = 'block';
            document.getElementById('methodSelect').style.display = 'block';
            document.getElementById('saveBtn').style.display = 'block';
            btn.textContent = '🔄 Re-extract';
            btn.disabled = false;

            status.className = 'success';
            status.textContent = '✅ Details extracted! Review and save.';
        } else {
            throw new Error(data.error || 'Extraction failed');
        }

    } catch (err) {
        status.className = 'error';
        status.textContent = '❌ ' + err.message;
        btn.disabled = false;
    }
});

document.getElementById('saveBtn').addEventListener('click', async () => {
    const status = document.getElementById('status');
    const method = document.getElementById('methodSelect').value;

    status.className = 'loading';
    status.textContent = '💾 Saving...';

    try {
        const response = await fetch(TRACKER_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ...extractedData, method, source_url: extractedData.source_url })
        });

        const data = await response.json();

        if (data.success) {
            status.className = 'success';
            status.textContent = '🎉 Saved to your tracker!';
            document.getElementById('saveBtn').style.display = 'none';
        } else {
            throw new Error(data.error || 'Save failed');
        }
    } catch (err) {
        status.className = 'error';
        status.textContent = '❌ ' + err.message;
    }
});