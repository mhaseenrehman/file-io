import { useToast } from "vue-toastification";

export function useFileValidator() {
    const toast = useToast();

    const validateFile = (file) => {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        const maxSizeInBytes = 5 * 1024 * 1024;

        if (!file) {
            toast.error("Please Select Images to Upload.", { timeout: 4000 });
            return false;
        }

        if (!allowedTypes.includes(file.type)) {
            toast.error("Invalid File type found. Only jpeg, png, webp allowed.", { timeout: 4000 });
            return false;
        }

        if (file.size > maxSizeInBytes) {
            toast.error("File Size too big. Only 5 mb allowed.", { timeout: 4000 });
            return false;
        }

        return true;
    }

    return {
        validateFile
    }
}