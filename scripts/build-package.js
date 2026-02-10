import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { execSync } from 'child_process';
import archiver from 'archiver';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const pluginRoot = path.resolve(__dirname, '..');

const packageName = 'unnotifier';
const outputDir = path.join(pluginRoot, 'compiled');
const packageItems = [
  'assets',
  'languages',
  'readme.txt',
  'src',
  'uninstall.php',
  'unnotifier.php'
];

const excludedDirectories = ['node_modules', 'compiled', 'scripts', 'svn'];
const excludedFiles = ['package.json', 'package-lock.json', '.DS_Store', 'Thumbs.db'];

function log(message) {
  console.log(message);
}

function getVersion() {
  const mainFile = path.join(pluginRoot, 'unnotifier.php');
  if (!fs.existsSync(mainFile)) {
    throw new Error('Unable to locate unnotifier.php to determine version.');
  }

  const content = fs.readFileSync(mainFile, 'utf8');
  const match = content.match(/Version:\s*([0-9.]+)/);

  if (!match) {
    throw new Error('Version not found in unnotifier.php header.');
  }

  return match[1];
}

function cleanOutputDirectory() {
  if (fs.existsSync(outputDir)) {
    fs.rmSync(outputDir, { recursive: true, force: true });
  }
  fs.mkdirSync(outputDir, { recursive: true });
}

function shouldSkipItem(itemPath) {
  const relative = path.relative(pluginRoot, itemPath).replace(/\\/g, '/');

  if (!relative) {
    return false;
  }

  if (excludedFiles.some((name) => relative.endsWith(name))) {
    return true;
  }

  return excludedDirectories.some((dir) => relative === dir || relative.startsWith(`${dir}/`));
}

function copyRecursively(source, destination, skipExcluded = true) {
  if (!fs.existsSync(source)) {
    return;
  }

  if (skipExcluded && shouldSkipItem(source)) {
    return;
  }

  const stat = fs.statSync(source);

  if (stat.isDirectory()) {
    fs.mkdirSync(destination, { recursive: true });
    const entries = fs.readdirSync(source);

    for (const entry of entries) {
      copyRecursively(path.join(source, entry), path.join(destination, entry), skipExcluded);
    }
  } else {
    fs.copyFileSync(source, destination);
  }
}

async function createZipArchive(sourceDir, zipPath, rootName) {
  await new Promise((resolve, reject) => {
    const output = fs.createWriteStream(zipPath);
    const archive = archiver('zip', { zlib: { level: 9 } });

    output.on('close', resolve);
    archive.on('error', reject);

    archive.pipe(output);
    archive.directory(sourceDir, rootName);
    archive.finalize();
  });
}

function syncToSvn(compiledDir, version) {
  const svnRoot = path.join(pluginRoot, 'svn');
  const tagsDir = path.join(svnRoot, 'tags');
  const trunkDir = path.join(svnRoot, 'trunk');

  if (!fs.existsSync(tagsDir)) {
    log('SVN tags directory not found, skipping SVN sync.');
    return;
  }

  let svnAvailable = true;
  try {
    execSync('svn --version', { stdio: 'ignore' });
  } catch (error) {
    svnAvailable = false;
  }

  const tagPath = path.join(tagsDir, version);

  if (fs.existsSync(tagPath)) {
    if (svnAvailable) {
      try {
        execSync(`svn delete "${tagPath}" --force`, { stdio: 'ignore' });
      } catch (error) {
        fs.rmSync(tagPath, { recursive: true, force: true });
      }
    } else {
      fs.rmSync(tagPath, { recursive: true, force: true });
    }
  }

  fs.mkdirSync(tagPath, { recursive: true });
  copyRecursively(compiledDir, tagPath, false);

  if (svnAvailable) {
    try {
      execSync(`svn add "${tagPath}" --force`, { stdio: 'ignore' });
    } catch (error) {
      log('Failed to add SVN tag automatically.');
    }
  }

  if (fs.existsSync(trunkDir)) {
    const trunkEntries = fs.readdirSync(trunkDir);
    for (const entry of trunkEntries) {
      if (entry === '.svn') {
        continue;
      }
      fs.rmSync(path.join(trunkDir, entry), { recursive: true, force: true });
    }

    copyRecursively(compiledDir, trunkDir, false);

    if (svnAvailable) {
      try {
        execSync(`svn add "${trunkDir}"/* --force`, { stdio: 'ignore' });
      } catch (error) {
        log('Failed to add trunk changes automatically.');
      }
    }
  }
}

async function buildPackage() {
  log('═════════════════════════════════════════════════════════');
  log('  Unnotifier Plugin Package Builder');
  log('═════════════════════════════════════════════════════════\n');

  const version = getVersion();
  const tempDir = path.join(outputDir, packageName);
  const zipPath = path.join(outputDir, `${packageName}-${version}.zip`);

  log(`Detected version: ${version}`);
  log(`Output directory: ${outputDir}`);

  cleanOutputDirectory();
  fs.mkdirSync(tempDir, { recursive: true });

  log('\nCopying plugin files...');
  for (const item of packageItems) {
    const source = path.join(pluginRoot, item);
    const destination = path.join(tempDir, item);
    if (fs.existsSync(source)) {
      log(`  • ${item}`);
      copyRecursively(source, destination);
    }
  }

  log('\nCreating ZIP archive...');
  await createZipArchive(tempDir, zipPath, packageName);
  log(`ZIP archive created: ${zipPath}`);

  log('\nSyncing to SVN snapshot directories...');
  syncToSvn(tempDir, version);

  log('\nBuild completed successfully!');
  log('═════════════════════════════════════════════════════════\n');
}

buildPackage().catch((error) => {
  console.error('Build failed:', error.message);
  process.exit(1);
});

