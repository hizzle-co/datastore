# GitHub Pages Setup Instructions

This repository now has comprehensive documentation in the `docs/` folder that can be hosted on GitHub Pages.

## Enable GitHub Pages

To host the documentation on GitHub Pages:

1. Go to your repository on GitHub
2. Click on **Settings**
3. Scroll down to **Pages** section (in the left sidebar under "Code and automation")
4. Under **Source**, select:
   - Source: **Deploy from a branch**
   - Branch: **main** (or your default branch)
   - Folder: **/docs**
5. Click **Save**

GitHub will automatically build and deploy your documentation. It will be available at:
```
https://hizzle-co.github.io/datastore/
```

## Documentation Structure

The `docs/` folder contains:

- **README.md** - Main documentation index
- **_config.yml** - Jekyll configuration for GitHub Pages
- **Component documentation files:**
  - store.md
  - collection.md
  - record.md
  - query.md
  - prop.md
  - rest-controller.md
  - list-table.md
  - webhooks.md
  - date-time.md
  - store-exception.md

## Customization

The documentation uses the **Cayman** theme. You can customize it by editing `docs/_config.yml`.

Available themes:
- jekyll-theme-cayman (current)
- jekyll-theme-minimal
- jekyll-theme-slate
- jekyll-theme-architect
- jekyll-theme-midnight
- And more...

## Local Testing

To test the documentation locally:

```bash
# Install Jekyll
gem install bundler jekyll

# Navigate to docs folder
cd docs

# Create Gemfile
echo 'source "https://rubygems.org"' > Gemfile
echo 'gem "github-pages", group: :jekyll_plugins' >> Gemfile

# Install dependencies
bundle install

# Serve locally
bundle exec jekyll serve

# Open http://localhost:4000 in your browser
```

## Updating Documentation

All documentation is written in Markdown. To update:

1. Edit the relevant `.md` file in the `docs/` folder
2. Commit and push your changes
3. GitHub Pages will automatically rebuild and deploy

## Links

After enabling GitHub Pages, you can link to it from your main README or other places:

```markdown
📚 [View Documentation](https://hizzle-co.github.io/datastore/)
```
