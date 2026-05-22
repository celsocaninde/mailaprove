# 🤝 Contributing to Mail Approve

Thank you for your interest in contributing to Mail Approve! We welcome all contributions, whether they are:

- 🐛 Bug reports
- 💡 Feature suggestions
- 📝 Documentation improvements
- 🌍 Translations
- 🔧 Code improvements and pull requests

## 📋 How to Contribute

### 1. Fork the Repository
Click the "Fork" button on the top-right of the repository page.

### 2. Clone Your Fork
```bash
git clone https://github.com/YOUR_USERNAME/mailaprove.git
cd mailaprove
```

### 3. Create a Feature Branch
```bash
git checkout -b feature/your-feature-name
# OR for bug fixes:
git checkout -b fix/bug-description
```

### 4. Make Your Changes
- Keep changes focused and atomic
- Follow PSR-12 PHP coding standards
- Write clear commit messages

### 5. Test Your Changes
- Test locally with GLPI 11
- Verify no breaking changes
- Check all locales if modifying strings

### 6. Commit & Push
```bash
git add .
git commit -m "feat: clear description of changes"
git push origin feature/your-feature-name
```

### 7. Create a Pull Request
1. Go to your fork on GitHub
2. Click "Pull Request" button
3. Provide a clear title and description
4. Reference any related issues (#123)

## 📝 Commit Message Format

Use clear, descriptive commit messages:
```
feat: add new feature X
fix: resolve issue with Y
docs: update installation guide
refactor: improve code quality
test: add test coverage for Z
```

## 🎨 Code Style

- **PHP**: PSR-12 standard
- **Indentation**: 4 spaces (no tabs)
- **Line length**: Max 120 characters when reasonable
- **Documentation**: Use PHPDoc comments for classes and public methods

## 🌍 Localization

To contribute translations:
1. Edit `.po` files in `locale/` directory
2. Use a PO editor (Poedit, VS Code PO extension, etc.)
3. Test translations locally
4. Submit pull request

## 🐛 Reporting Bugs

When reporting bugs, please include:
- **PHP Version**: `php -v`
- **GLPI Version**: From GLPI settings
- **Description**: What happened?
- **Steps to Reproduce**: How to reproduce the bug
- **Expected vs Actual**: What should happen vs what happened
- **Screenshots/Logs**: Error messages, logs, screenshots

## 💡 Feature Requests

Include:
- **Clear Description**: What feature do you want?
- **Use Case**: Why is this needed?
- **Example**: How should it work?
- **Alternative Solutions**: Any other approaches?

## ✅ Pull Request Guidelines

- One feature/fix per PR (keep them focused)
- Update README if adding user-facing features
- Add/update locale `.po` files for new strings
- Test with GLPI 11 before submitting
- Keep commit history clean (squash if needed)

## 📚 Development Setup

### Requirements
- PHP 8.2+
- Composer
- GLPI 11.x running instance

### Setup
```bash
# Install dependencies
composer install

# Run tests (if available)
./vendor/bin/phpunit

# Check code standards
./vendor/bin/phpcs --standard=PSR12 src/
```

## ⚖️ License

By contributing, you agree that your contributions will be licensed under the same GPLv3+ license as the project.

## 💬 Questions?

- Open an [Issue](https://github.com/YOUR_REPO/mailaprove/issues)
- Join [Discussions](https://github.com/YOUR_REPO/mailaprove/discussions)
- Check the [Wiki](https://github.com/YOUR_REPO/mailaprove/wiki)

---

**Thanks for making Mail Approve better!** ❤️
