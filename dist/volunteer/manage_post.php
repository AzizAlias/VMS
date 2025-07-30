<?php
session_start();
require_once 'config.php';
// Check if the user is logged in as a volunteer
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: log_in_volunteer.php");
    exit();
}


// Handle post editing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $post_id = $_POST['post_id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $existing_images = $_POST['existing_images']; // Existing images (comma-separated)
    $removed_images = $_POST['removed_images']; // Removed images (comma-separated)
    $volunteer_id = $_SESSION['volunteer_id'];

    // Step 1: Remove deleted images from the file system
    if (!empty($removed_images)) {
        $removed_images_array = explode(",", rtrim($removed_images, ",")); // Convert to array
        foreach ($removed_images_array as $image_path) {
            if (file_exists($image_path)) {
                unlink($image_path); // Delete the image file
            }
        }
    }

    // Step 2: Upload new images
    $new_image_paths = [];
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); // Create directory if it doesn't exist
        }

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $image_name = basename($_FILES['images']['name'][$key]);
            $target_file = $upload_dir . uniqid() . "_" . $image_name;

            // Move the uploaded file to the target directory
            if (move_uploaded_file($tmp_name, $target_file)) {
                $new_image_paths[] = $target_file;
            } else {
                echo "Failed to upload image: " . $image_name;
            }
        }
    }

    // Step 3: Update the database with the new set of images
    // Combine existing images (excluding removed ones) with new images
    $existing_images_array = explode(",", $existing_images);
    $remaining_images = array_diff($existing_images_array, explode(",", rtrim($removed_images, ",")));
    $all_images = array_merge($remaining_images, $new_image_paths);
    $image_paths_str = implode(",", $all_images);

    // Update the post in the database
    $sql = "UPDATE discussion_board SET title = ?, content = ?, image_path = ? WHERE post_id = ? AND volunteer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssii", $title, $content, $image_paths_str, $post_id, $volunteer_id);

    if ($stmt->execute()) {
        header("Location: manage_post.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}

// Handle post deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $post_id = $_POST['post_id'];
    $volunteer_id = $_SESSION['volunteer_id'];

    // Delete the post from the database
    $sql = "DELETE FROM discussion_board WHERE post_id = ? AND volunteer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $post_id, $volunteer_id);

    if ($stmt->execute()) {
        header("Location: manage_post.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}

// Fetch all posts by the logged-in user
$volunteer_id = $_SESSION['volunteer_id'];
$sql = "SELECT * FROM discussion_board WHERE volunteer_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $volunteer_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Posts</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <style>
        /* Custom CSS for image aspect ratio */
        .carousel-image {
            width: 100%;
            height: 100%;
            object-fit: contain; /* Ensures the image maintains its aspect ratio */
        }
        .carousel-container {
            height: 400px; /* Set a fixed height for the carousel container */
            position: relative;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-gray-100">

<?php include 'volunteer_sidebar.php'; ?>

<!-- Main Content -->
<div class="p-4 lg:ml-72 pt-12"> 
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">Manage Posts</h1>

        <!-- Display Posts -->
        <div class="mt-8">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                        <h2 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($row['title']); ?></h2>
                        <p class="text-gray-700 mb-4"><?php echo htmlspecialchars($row['content']); ?></p>
                        <?php if ($row['image_path']): ?>
                            <?php $image_paths = explode(",", $row['image_path']); ?>
                            <!-- Carousel for multiple images -->
                            <div id="carousel-<?php echo $row['post_id']; ?>" class="relative w-full carousel-container" data-carousel="static">
                                <!-- Carousel wrapper -->
                                <div class="relative h-full overflow-hidden rounded-lg">
                                    <?php foreach ($image_paths as $index => $image_path): ?>
                                        <div class="hidden duration-700 ease-in-out" data-carousel-item>
                                            <img src="<?php echo htmlspecialchars($image_path); ?>" 
                                                 alt="Post Image" 
                                                 class="carousel-image absolute block w-full -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <!-- Slider controls -->
                                <button type="button" class="absolute top-0 start-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none" data-carousel-prev>
                                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/30 group-hover:bg-white/50 group-focus:ring-4 group-focus:ring-white group-focus:outline-none">
                                        <svg class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 1 1 5l4 4"/>
                                        </svg>
                                        <span class="sr-only">Previous</span>
                                    </span>
                                </button>
                                <button type="button" class="absolute top-0 end-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none" data-carousel-next>
                                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/30 group-hover:bg-white/50 group-focus:ring-4 group-focus:ring-white group-focus:outline-none">
                                        <svg class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                        </svg>
                                        <span class="sr-only">Next</span>
                                    </span>
                                </button>
                            </div>
                        <?php endif; ?>
                        <p class="text-sm text-gray-500 mt-4">Posted on: <?php echo htmlspecialchars($row['created_at']); ?></p>

                        <!-- Edit and Delete Buttons -->
                        <div class="mt-4 flex space-x-4">
                            <button onclick="toggleForm('edit-post-form-<?php echo $row['post_id']; ?>')" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">Edit</button>
                            <form method="POST" action="manage_post.php" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="post_id" value="<?php echo $row['post_id']; ?>">
                                <button type="submit" class="text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5">Delete</button>
                            </form>
                        </div>

                        <!-- Edit Post Form -->
                        <form id="edit-post-form-<?php echo $row['post_id']; ?>" class="bg-white p-8 rounded-lg shadow-md space-y-6 hidden" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="post_id" value="<?php echo $row['post_id']; ?>">
                            <input type="hidden" id="existing-images-<?php echo $row['post_id']; ?>" name="existing_images" value="<?php echo htmlspecialchars($row['image_path']); ?>">
                            <input type="hidden" id="removed-images-<?php echo $row['post_id']; ?>" name="removed_images" value="">

                            <div>
                                <label for="title-<?php echo $row['post_id']; ?>" class="block mb-2 text-sm font-medium text-gray-900">Post Title</label>
                                <input type="text" id="title-<?php echo $row['post_id']; ?>" name="title" value="<?php echo htmlspecialchars($row['title']); ?>" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            </div>

                            <div>
                                <label for="content-<?php echo $row['post_id']; ?>" class="block mb-2 text-sm font-medium text-gray-900">Post Content</label>
                                <textarea id="content-<?php echo $row['post_id']; ?>" name="content" required rows="6" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"><?php echo htmlspecialchars($row['content']); ?></textarea>
                            </div>

                            <!-- Display Existing Images -->
                            <?php if ($row['image_path']): ?>
                                <?php $image_paths = explode(",", $row['image_path']); ?>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    <?php foreach ($image_paths as $image_path): ?>
                                        <div id="image-container-<?php echo base64_encode($image_path); ?>" class="relative">
                                            <img src="<?php echo htmlspecialchars($image_path); ?>" 
                                                alt="Post Image" 
                                                class="carousel-image">
                                            <button type="button" 
                                                    onclick="removeImage('<?php echo htmlspecialchars($image_path); ?>', <?php echo $row['post_id']; ?>)" 
                                                    class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div>
                                <label for="images-<?php echo $row['post_id']; ?>" class="block mb-2 text-sm font-medium text-gray-900">Upload New Images (optional)</label>
                                <input type="file" id="images-<?php echo $row['post_id']; ?>" name="images[]" multiple class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                            </div>

                            <div class="flex justify-between">
                                <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">Update Post</button>
                                <button type="button" onclick="toggleForm('edit-post-form-<?php echo $row['post_id']; ?>')" class="text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5">Cancel</button>
                            </div>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No posts available.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Function to open the modal and set the image source
    function openModal(imageSrc) {
        const modal = document.getElementById('imageModal');
        const modalImage = document.getElementById('modalImage');
        modalImage.src = imageSrc;
        modal.classList.remove('hidden');
    }

    // Function to close the modal
    function closeModal() {
        const modal = document.getElementById('imageModal');
        modal.classList.add('hidden');
    }

    function toggleForm(formId) {
        const form = document.getElementById(formId);
        form.classList.toggle('hidden');
    }

    function removeImage(imagePath, postId) {
        // Add the image path to the removed_images hidden input
        const removedImagesInput = document.getElementById(`removed-images-${postId}`);
        removedImagesInput.value += `${imagePath},`;

        // Hide the image from the UI
        const imageContainer = document.getElementById(`image-container-${btoa(imagePath)}`);
        imageContainer.classList.add('hidden');
    }
</script>
</body>
</html>