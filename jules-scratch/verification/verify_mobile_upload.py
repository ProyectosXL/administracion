from playwright.sync_api import sync_playwright, expect

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        # iPhone 13 Pro user agent
        context = browser.new_context(
            user_agent='Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1',
            viewport={"width": 390, "height": 844}
        )
        page = context.new_page()

        # Navigate to the page
        page.goto("http://localhost:8000/tesoreria/cargaGastos.php")

        # Click the first upload button
        upload_button = page.locator('.btn-primary.btn-icon').first
        upload_button.click()

        # Wait for the SweetAlert modal to be visible
        expect(page.locator(".swal2-popup")).to_be_visible()

        # Take a screenshot
        page.screenshot(path="jules-scratch/verification/verification.png")

        browser.close()

if __name__ == '__main__':
    run()
