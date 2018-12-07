<?php

namespace App\Controller;

use App\Message\GetBets;
use App\Message\RegisterBet;
use App\Message\ReportGameResult;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Live\Message\LiveViewUpdate;
use Symfony\Component\Live\Subscription;
use Symfony\Component\Live\SubscriptionList;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    use HandleTrait;

    private $messageBus;

    public function __construct(MessageBusInterface $bus)
    {
        $this->messageBus = $bus;
    }

    /**
     * @Route("/", name="home")
     */
    public function index()
    {
        $bets = $this->handle(new GetBets());

        return $this->render('index.html.twig', [
            'bets' => $bets,
        ]);
    }

    /**
     * @Route("/bets/{game}", name="bets_for_game")
     */
    public function betsForGame($game)
    {
        $bets = $this->messageBus->dispatch(GetBets::forGame($game));

        return $this->render('game.html.twig', [
            'bets' => $bets,
            'game' => $game,
        ]);
    }

    /**
     * @Route("/bet", methods={"POST"}, name="do_bet")
     */
    public function bet(Request $request)
    {
        $this->messageBus->dispatch(new RegisterBet(
            $game = $request->request->get('game'),
            $request->request->getInt('left'),
            $request->request->getInt('right')
        ));

        $this->messageBus->dispatch(new LiveViewUpdate([
            'bets',
            'game-'.$game,
        ]));

        return $this->redirectToRoute('home');
    }
}
