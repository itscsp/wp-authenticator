# WP Authenticator - GitHub Actions

This repository uses GitHub Actions for automated testing, building, and releasing.

## 🔄 Workflows

### 1. Test and Validate (`test.yml`)
- **Triggers:** Push to main/develop, Pull Requests
- **Tests:** PHP 7.4, 8.0, 8.1, 8.2
- **Validates:** Composer files, PHP syntax, plugin structure

### 2. Build and Release (`build-release.yml`)
- **Triggers:** Git tags (v*), Manual dispatch
- **Builds:** Production-ready plugin package
- **Releases:** Automatic GitHub releases with assets

## 🚀 Usage

### Automatic Release (Recommended)
1. Create and push a version tag:
   ```bash
   git tag v1.0.1
   git push origin v1.0.1
   ```
2. GitHub Actions will automatically:
   - Build the plugin
   - Create a GitHub release
   - Upload the plugin package

### Manual Release
1. Go to **Actions** tab in GitHub
2. Select **Build and Release WP Authenticator**
3. Click **Run workflow**
4. Enter version number (e.g., v1.0.1)
5. Click **Run workflow**

## 📦 What Gets Built

The GitHub Action creates a production package containing:
- Plugin PHP files
- Composer dependencies (production only)
- API documentation
- Optimized autoloader
- No development files

## 🔧 Configuration

### Required Secrets
- `GITHUB_TOKEN` (automatically provided)

### Environment Requirements
- PHP 8.1
- Composer v2
- Ubuntu Latest

## 📋 Release Process

1. **Development** → Push to `develop` branch
2. **Testing** → Create PR to `main` (triggers tests)
3. **Release** → Merge to `main` and create version tag
4. **Distribution** → GitHub Actions builds and releases automatically

## 🎯 Benefits

- ✅ Automated testing across PHP versions
- ✅ Consistent build process
- ✅ Automatic releases on tags
- ✅ Production-ready packages
- ✅ Version management
- ✅ Release notes generation
