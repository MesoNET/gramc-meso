from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service as ChromeService
from webdriver_manager.chrome import ChromeDriverManager


# See https://www.selenium.dev/documentation/webdriver/getting_started/first_script/
def test_eight_components():
    driver = webdriver.Chrome(service=ChromeService(executable_path=ChromeDriverManager().install()))

    driver.get("https://gramc-meso0.dev.calmip.univ-toulouse.fr/gramc3/connexion_dbg")

    title = driver.title
    assert title == "Plateforme d'attribution de ressources de MESONET"

    driver.implicitly_wait(0.5)

    text_box = driver.find_element(by=By.NAME, value="form[data]")
    submit_button = driver.find_element(by=By.CSS_SELECTOR, value="button")

    text_box.send_keys("Rogogo trouloulou")
    submit_button.click()

    message = driver.find_element(by=By.CSS_SELECTOR, value="h2")
    value = message.text
    assert value == "Bienvenue sur Mesonet"

    connected = driver.find_element(by=By.CSS_SELECTOR, value="div.sous_header div.profil a").text
    print(f"coucou connected {connected}")
    assert connected.startswith("Emmanuel Courcelle")

    driver.quit()


test_eight_components()

