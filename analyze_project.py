import os
import requests
# from github import Github

# Ваш GitHub токен
GITHUB_TOKEN = "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"
REPO_OWNER = "Weedston"
REPO_NAME = "online-wallet"

def get_repo_contents(path="."):
    """Получение содержимого репозитория."""
    files = []
    directories = []
    for root, dirs, filenames in os.walk(path):
        for dir_name in dirs:
            directories.append(os.path.join(root, dir_name))
        for file_name in filenames:
            files.append(os.path.join(root, file_name))
    return files, directories

def generate_file_structure(path=".", indent=0):
    """Генерация структуры файлов."""
    files, directories = get_repo_contents(path)
    structure = ""
    
    for directory in sorted(directories):
        structure += " " * indent + f"├── {os.path.basename(directory)}\n"
        structure += generate_file_structure(directory, indent + 4)
    
    for file in sorted(files):
        structure += " " * indent + f"├── {os.path.basename(file)}\n"
    
    return structure

def update_readme():
    """Обновление README.md со структурой файлов."""
    structure = "## Структура файлов\n\n```\n"
    structure += generate_file_structure()
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
    update_readme()
