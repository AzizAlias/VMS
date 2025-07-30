<?php
session_start();

require_once 'config.php';

// Check if the user is logged in as a volunteer
if (!isset($_SESSION['volunteer_id'])) {
    header("Location: log_in_volunteer.php");
    exit();
}

// Handle form submission for creating a post
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $volunteer_id = $_SESSION['volunteer_id']; // Use logged-in volunteer's ID

    $image_paths = []; // Initialize as an empty array

    // Check if images are uploaded
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
                $image_paths[] = $target_file;
            } else {
                echo "Failed to upload image: " . $image_name;
            }
        }
    }

    // Insert the post into the database
    $sql = "INSERT INTO discussion_board (volunteer_id, title, content, image_path) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    // Convert the array of image paths to a comma-separated string
    $image_paths_str = implode(",", $image_paths);
    $stmt->bind_param("isss", $volunteer_id, $title, $content, $image_paths_str);

    if ($stmt->execute()) {
        header("Location: discussion_board.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}

// Handle like/unlike functionality
if (isset($_POST['like_post_id'])) {
    $post_id = $_POST['like_post_id'];
    $volunteer_id = $_SESSION['volunteer_id'];

    // Check if the user has already liked the post
    $check_like_sql = "SELECT * FROM post_likes WHERE post_id = ? AND volunteer_id = ?";
    $check_like_stmt = $conn->prepare($check_like_sql);
    $check_like_stmt->bind_param("ii", $post_id, $volunteer_id);
    $check_like_stmt->execute();
    $result = $check_like_stmt->get_result();

    if ($result->num_rows == 0) {
        // If the user hasn't liked the post, insert a new like
        $like_sql = "INSERT INTO post_likes (post_id, volunteer_id) VALUES (?, ?)";
        $like_stmt = $conn->prepare($like_sql);
        $like_stmt->bind_param("ii", $post_id, $volunteer_id);
        $like_stmt->execute();
        $action = 'like';
    } else {
        // If the user has already liked the post, remove the like (unlike)
        $unlike_sql = "DELETE FROM post_likes WHERE post_id = ? AND volunteer_id = ?";
        $unlike_stmt = $conn->prepare($unlike_sql);
        $unlike_stmt->bind_param("ii", $post_id, $volunteer_id);
        $unlike_stmt->execute();
        $action = 'unlike';
    }

    // Fetch the updated like count
    $like_count_sql = "SELECT COUNT(*) AS like_count FROM post_likes WHERE post_id = ?";
    $like_count_stmt = $conn->prepare($like_count_sql);
    $like_count_stmt->bind_param("i", $post_id);
    $like_count_stmt->execute();
    $like_count_result = $like_count_stmt->get_result();
    $like_count_row = $like_count_result->fetch_assoc();
    $like_count = $like_count_row['like_count'];

    // Return a JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'action' => $action,
        'like_count' => $like_count
    ]);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'comment') {
    $post_id = $_POST['post_id'];
    $comment_text = $_POST['comment_text'];
    $volunteer_id = $_SESSION['volunteer_id'];

 
    $sql = "INSERT INTO comments (post_id, volunteer_id, comment_text) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $post_id, $volunteer_id, $comment_text);

    if ($stmt->execute()) {
        header("Location: discussion_board.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}


$sql = "SELECT db.post_id, db.title, db.content, db.image_path, db.volunteer_id, v.volunteer_name AS volunteer_name,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = db.post_id) AS like_count
        FROM discussion_board db
        JOIN volunteer v ON db.volunteer_id = v.volunteer_id
        ORDER BY db.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discussion Board</title>
    <!-- Include Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Include Flowbite CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet">
    <style>
        /* Custom CSS for Instagram-like feed */
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 16px;
        }
        .image-container {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            aspect-ratio: 1 / 1; /* Make the container square */
        }
        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover; /* Ensure the image covers the square container */
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'volunteer_sidebar.php'; ?>

    <div class="p-4 lg:ml-72 pt-12"> 
        <div class="container mx-auto px-4 py-8">
            <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">Discussion Board</h1>

            <!-- Button to show the Create Post form -->
            <button onclick="toggleForm('create-post-form')" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mb-6">
                Create Post
            </button>

            <!-- Create Post Form -->
            <form id="create-post-form" class="bg-white p-8 rounded-lg shadow-md space-y-6 hidden" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                <div>
                    <label for="title" class="block mb-2 text-sm font-medium text-gray-900">Post Title</label>
                    <input type="text" id="title" name="title" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                </div>

                <div>
                    <label for="content" class="block mb-2 text-sm font-medium text-gray-900">Post Content</label>
                    <textarea id="content" name="content" required rows="6" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"></textarea>
                </div>

                <div>
                    <label for="images" class="block mb-2 text-sm font-medium text-gray-900">Upload Images (optional)</label>
                    <input type="file" id="images" name="images[]" multiple class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none" onchange="previewImages(event)">
                </div>

                <!-- Image Preview Section -->
                <div id="image-preview" class="grid grid-cols-3 gap-4 mt-4"></div>

                <div class="flex justify-between">
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">Submit Post</button>
                    <button type="button" onclick="toggleForm('create-post-form')" class="text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5">Cancel</button>
                </div>
            </form>

            <!-- Display Posts -->
            <div class="mt-8 space-y-6">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        // Check if the current user has liked this post
                        $check_like_sql = "SELECT * FROM post_likes WHERE post_id = ? AND volunteer_id = ?";
                        $check_like_stmt = $conn->prepare($check_like_sql);
                        $check_like_stmt->bind_param("ii", $row['post_id'], $_SESSION['volunteer_id']);
                        $check_like_stmt->execute();
                        $like_result = $check_like_stmt->get_result();

                        // Fetch comments for this post
                        $comment_sql = "SELECT c.comment_text, c.created_at, v.volunteer_name 
                                       FROM comments c 
                                       JOIN volunteer v ON c.volunteer_id = v.volunteer_id 
                                       WHERE c.post_id = ? 
                                       ORDER BY c.created_at ASC";
                        $comment_stmt = $conn->prepare($comment_sql);
                        $comment_stmt->bind_param("i", $row['post_id']);
                        $comment_stmt->execute();
                        $comment_result = $comment_stmt->get_result();
                        ?>
                        <!-- Post Card -->
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h2 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($row['title']); ?></h2>
                            <p class="text-gray-700 mb-4"><?php echo htmlspecialchars($row['content']); ?></p>
                            <?php if ($row['image_path']): ?>
                                <?php $image_paths = explode(",", $row['image_path']); ?>
                                <!-- Image Grid for Instagram-like feed -->
                                <div class="image-grid">
                                    <?php foreach ($image_paths as $index => $image_path): ?>
                                        <div class="image-container" onclick="openModal('<?php echo htmlspecialchars($image_path); ?>')">
                                            <img src="<?php echo htmlspecialchars($image_path); ?>" 
                                                 alt="Post Image" 
                                                 class="rounded-lg">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <p class="text-sm text-gray-500 mt-4">Posted by: <?php echo htmlspecialchars($row['volunteer_name']); ?></p>

                            <!-- Like button and like count -->
                            <div class="mt-4 flex items-center">
                                <button onclick="likePost(<?php echo $row['post_id']; ?>)" 
                                        class="like-button flex items-center justify-center w-10 h-10 rounded-full <?php echo ($like_result->num_rows > 0) ? 'liked bg-red-500 hover:bg-red-600' : 'unliked bg-gray-500 hover:bg-gray-600'; ?>"
                                        data-post-id="<?php echo $row['post_id']; ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(255, 255, 255, 1);">
                                        <path d="M4 21h1V8H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2zM20 8h-7l1.122-3.368A2 2 0 0 0 12.225 2H12L7 7.438V21h11l3.912-8.596L22 12v-2a2 2 0 0 0-2-2z"></path>
                                    </svg>
                                </button>
                                <p class="ml-2 text-sm text-gray-500 like-count" data-post-id="<?php echo $row['post_id']; ?>">
                                    <?php echo $row['like_count']; ?> Likes
                                </p>
                            </div>

                            <!-- Comment Section -->
                            <div class="mt-6">
                                <h3 class="text-lg font-semibold mb-4">Comments</h3>
                                <?php if ($comment_result->num_rows > 0): ?>
                                    <?php while ($comment = $comment_result->fetch_assoc()): ?>
                                        <div class="bg-gray-50 p-4 rounded-lg mb-2">
                                            <p class="text-gray-700"><?php echo htmlspecialchars($comment['comment_text']); ?></p>
                                            <p class="text-sm text-gray-500 mt-1">By: <?php echo htmlspecialchars($comment['volunteer_name']); ?> on <?php echo htmlspecialchars($comment['created_at']); ?></p>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <p class="text-gray-500">No comments yet.</p>
                                <?php endif; ?>
                            </div>

                            <!-- Add Comment Form -->
                            <form method="POST" action="discussion_board.php" class="mt-4">
                                <input type="hidden" name="action" value="comment">
                                <input type="hidden" name="post_id" value="<?php echo $row['post_id']; ?>">
                                <textarea name="comment_text" rows="2" class="w-full p-2 border border-gray-300 rounded-lg" placeholder="Add a comment..." required></textarea>
                                <button type="submit" class="mt-2 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2">Post Comment</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center text-gray-500">No posts available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="image-modal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-75 flex items-center justify-center p-4">
        <!-- Overlay that covers the entire screen -->
        <div class="absolute inset-0" onclick="closeModal()"></div>
        
        <!-- Modal Content -->
        <div class="bg-white rounded-lg shadow-lg max-w-3xl w-full relative">
            <!-- Close Button -->
            <button onclick="closeModal()" class="absolute -top-10 right-0 p-2 text-white hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            <!-- Modal Image -->
            <div class="p-4">
                <img id="modal-image" src="" alt="Modal Image" class="w-full h-auto rounded-lg">
            </div>
        </div>
    </div>

    <!-- Include Flowbite JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <script>
        // Function to toggle the visibility of a form
        function toggleForm(formId) {
            const form = document.getElementById(formId);
            form.classList.toggle('hidden');
        }

        // Function to preview uploaded images
        function previewImages(event) {
            const imagePreview = document.getElementById('image-preview');
            imagePreview.innerHTML = ''; // Clear previous previews

            const files = event.target.files;
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();

                reader.onload = function (e) {
                    const imgContainer = document.createElement('div');
                    imgContainer.className = 'relative image-container'; // Add the image-container class

                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'w-full h-full object-cover rounded-lg'; // Ensure the image covers the container

                    const removeButton = document.createElement('button');
                    removeButton.className = 'absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 focus:outline-none';
                    removeButton.innerHTML = '✕';
                    removeButton.onclick = function () {
                        removeImage(i);
                    };

                    imgContainer.appendChild(img);
                    imgContainer.appendChild(removeButton);
                    imagePreview.appendChild(imgContainer);
                };

                reader.readAsDataURL(file);
            }
        }

        // Function to remove an image from the preview and file input
        function removeImage(index) {
            const fileInput = document.getElementById('images');
            const files = Array.from(fileInput.files);

            // Remove the file at the specified index
            files.splice(index, 1);

            // Update the file input
            const dataTransfer = new DataTransfer();
            files.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files;

            // Re-render the preview
            previewImages({ target: fileInput });
        }

        // Function to handle liking a post
        function likePost(postId) {
            // Create a FormData object to send the post ID
            const formData = new FormData();
            formData.append('like_post_id', postId);

            // Send an AJAX request to the server
            fetch('discussion_board.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json()) // Parse the JSON response
            .then(data => {
                if (data.success) {
                    // Update the like button and like count
                    const likeButton = document.querySelector(`.like-button[data-post-id="${postId}"]`);
                    const likeCount = document.querySelector(`.like-count[data-post-id="${postId}"]`);

                    if (data.action === 'like') {
                        likeButton.classList.remove('unliked', 'bg-gray-500', 'hover:bg-gray-600');
                        likeButton.classList.add('liked', 'bg-red-500', 'hover:bg-red-600');
                    } else if (data.action === 'unlike') {
                        likeButton.classList.remove('liked', 'bg-red-500', 'hover:bg-red-600');
                        likeButton.classList.add('unliked', 'bg-gray-500', 'hover:bg-gray-600');
                    }

                    // Update the like count
                    likeCount.textContent = `${data.like_count} Likes`;
                } else {
                    console.error('Error:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Function to open the image modal
        function openModal(imageSrc) {
            const modal = document.getElementById('image-modal');
            const modalImage = document.getElementById('modal-image');
            modalImage.src = imageSrc;
            modal.classList.remove('hidden');
        }

       
        function closeModal() {
            const modal = document.getElementById('image-modal');
            modal.classList.add('hidden');
        }

       
        document.querySelector('#image-modal > div.bg-white').addEventListener('click', function(event) {
            event.stopPropagation(); // Stop the click event from bubbling up to the overlay
        });
    </script>
</body>
</html>