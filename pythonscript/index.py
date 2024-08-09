import os
import cv2
import imagehash
from PIL import Image

def hash_image(image_path):
    image = Image.open(image_path)
    return imagehash.average_hash(image)

def compare_images(hash1, hash2, threshold=2):
    return hash1 - hash2 < threshold

def process_folder(folder_path, reference_images, threshold=2):
    for root, dirs, files in os.walk(folder_path):
        for file in files:
            file_path = os.path.join(root, file)
            print(f"Checking {file_path}...")
            try:
                file_hash = hash_image(file_path)
                for ref_image_path in reference_images:
                    ref_hash = hash_image(ref_image_path)
                    if compare_images(file_hash, ref_hash, threshold):
                        print(f"Deleting {file_path} (matched with {ref_image_path})")
                        os.remove(file_path)
                        break
            except Exception as e:
                print(f"Error processing {file_path}: {e}")

if __name__ == "__main__":
    # Specify the paths to the reference images
    reference_images = [
        "C:/laragon/www/CURL-PHP-ImageConversion/pythonscript/ref_images/tma5kw8xtkkcszqp.jpg",
        "C:/laragon/www/CURL-PHP-ImageConversion/pythonscript/ref_images/r18r320q9xrdpzaw.jpg",
        "C:/laragon/www/CURL-PHP-ImageConversion/pythonscript/ref_images/45vy4sfatc84u6b2.jpg",
        "C:/laragon/www/CURL-PHP-ImageConversion/pythonscript/ref_images/img2.jpg",
        "C:/laragon/www/CURL-PHP-ImageConversion/pythonscript/ref_images/img3.jpg",
        "C:/laragon/www/CURL-PHP-ImageConversion/pythonscript/ref_images/t34p69cpcuhyuj93.jpg",
        "C:/laragon/www/CURL-PHP-ImageConversion/pythonscript/ref_images/2ewtppeadetk2zen.jpg",
        # Add more reference images as needed
    ]

    # Specify the folder to check
    # folder_to_check = "M:/images/products/img3"
    threshold = 10
    for i in range(51, 105):
        folder_to_check = f"M:/images/products/img{i}"
        print(f"Processing folder: {folder_to_check}")
        process_folder(folder_to_check, reference_images, threshold)
    # Process the folder
    # process_folder(folder_to_check, reference_images)
