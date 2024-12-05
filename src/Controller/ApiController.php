namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ApiController extends AbstractController
{
    /**
     * @Route("/api/upload", name="api_upload", methods={"POST"})
     */
    public function upload(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $file = $request->files->get('file');
        if ($file->getClientOriginalExtension() !== 'csv') {
            return $this->json(['error' => 'Invalid file format'], Response::HTTP_BAD_REQUEST);
        }

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return $this->json(['error' => 'Unable to open file'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        fgetcsv($handle); // Skip the header

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $user = new User();
            $user->setName($data[0]);
            $user->setEmail($data[1]);
            $user->setUsername($data[2]);
            $user->setAddress($data[3]);
            $user->setRole($data[4]);

            $em->persist($user);

            // Send email
            $email = (new Email())
                ->from('admin@example.com')
                ->to($data[1])
                ->subject('Welcome!')
                ->text('Hello ' . $data[0] . ', your account has been created.');

            $mailer->send($email);
        }

        $em->flush();
        fclose($handle);

        return $this->json(['message' => 'File uploaded and data stored successfully'], Response::HTTP_CREATED);
    }
}
// ...

/**
 * @Route("/api/users", name="api_users", methods={"GET"})
 */
public function getUsers(EntityManagerInterface $em): Response
{
    $users = $em->getRepository(User::class)->findAll();
    return $this->json($users);
}
// ...

/**
 * @Route("/api/backup", name="api_backup", methods={"GET"})
 */
public function backup(EntityManagerInterface $em): Response
{
    // Implement the logic to generate a backup file (backup.sql)
    // You may use mysqldump command to achieve this

    return $this->json(['message' => 'Database backup created successfully']);
}

/**
 * @Route("/api/restore", name="api_restore", methods={"POST"})
 */
public function restore(Request $request): Response
{
    $file = $request->files->get('file');
    if ($file->getClientOriginalExtension() !== 'sql') {
        return $this->json(['error' => 'Invalid file format'], Response::HTTP_BAD_REQUEST);
    }

    // Implement the logic to restore the database from backup file (backup.sql)
    // You may use mysql command to achieve this

    return $this->json(['message' => 'Database restored successfully']);
}
