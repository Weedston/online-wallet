import os

def get_repo_contents(paths):
    """Получение содержимого репозитория для указанных путей."""
    files = []
    directories = []
    
    for path in paths:
        for root, dirs, filenames in os.walk(path):
            for dir_name in dirs:
                directories.append(os.path.join(root, dir_name))
            for file_name in filenames:
                files.append(os.path.join(root, file_name))
    
    return files, directories

def generate_file_structure(paths, indent=0):
    """Генерация структуры файлов для указанных путей."""
    files, directories = get_repo_contents(paths)
    structure = ""
    
    for directory in sorted(directories):
        structure += " " * indent + f"├── {os.path.basename(directory)}\n"
        structure += generate_file_structure([directory], indent + 4)
    
    for file in sorted(files):
        structure += " " * indent + f"├── {os.path.basename(file)}\n"
    
    return structure

def update_readme(paths):
    """Обновление README.md со структурой файлов."""
    structure = "## Структура файлов\n\n```\n"
    structure += generate_file_structure(paths)
    structure += "```\n"
    
    try:
        with open("README.md", "r", encoding="utf-8") as readme_file:
            readme_content = readme_file.read()
        
        new_readme_content = readme_content + "\n" + structure
        
        with open("README.md", "w", encoding="utf-8") as readme_file:
            readme_file.write(new_readme_content)
        
        print("README.md обновлен успешно!")
    except Exception as e:
        print(f"Error updating README.md: {e}")

if __name__ == "__main__":
    # Укажите папки, которые нужно сканировать
    folders_to_scan = ["pages", "config", "css", "images", "js", "src"]
    update_readme(folders_to_scan)
