import { initCalculatorForm } from './calculator/form-controller';
import { initLanguageSelect } from './calculator/language-select';
import { initThemeToggle } from './calculator/theme';

document.addEventListener('DOMContentLoaded', () => {
    initCalculatorForm();
    initLanguageSelect();
    initThemeToggle();
});
