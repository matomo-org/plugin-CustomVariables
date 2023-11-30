/*!
 * Matomo - free/libre analytics platform
 *
 * Screenshot integration tests.
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

describe("CustomVariables", function () {
    this.timeout(0);

    this.fixture = "Piwik\\Plugins\\CustomVariables\\tests\\Fixtures\\VisitWithManyCustomVariables";

    it('should show an overview of all used custom variables', async function() {
        await page.goto("?idSite=1&period=day&date=2010-01-03&module=CustomVariables&action=manage");
        await page.waitForNetworkIdle();

        pageWrap = await page.$('.pageWrap');
        await page.evaluate(function () {
          $('#secondNavBar').css('visibility', 'hidden'); // hide navbar so shadow isn't shown on screenshot
        });
        expect(await pageWrap.screenshot()).to.matchImage('manage');
    });

    it('should be visible in the menu', async function() {
        await page.evaluate(function () {
          $('#secondNavBar').css('visibility', 'visible'); // show navbar again
        });
        expect(await page.screenshotSelector('li:contains(Diagnostic)')).to.matchImage('link_in_menu');
    });
});
