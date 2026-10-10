using System;
using System.Collections.Generic;
using System.Threading;
using Jypeli;
using Jypeli.Assets;
using Jypeli.Controls;
using Jypeli.Widgets;

public class Colesteroids : PhysicsGame
{
    const int ruudunLeveys = 100;
    const int ruudunKorkeus = 100;

    public override void Begin()
    {
        LuoKentta();
        Pahis();
        Hyvis();


        Camera.ZoomToLevel();
        Level.Background.Color = Color.Black;

        PhysicsObject pelaaja = new PhysicsObject(50.0, 50.0, Shape.Heart);
        pelaaja.Color = Color.HotPink;
        pelaaja.Y = Level.Bottom + 200.0;
        Add(pelaaja);
        
        Gravity = new Vector(0, -400);

        Level.CreateBorders();

        pelaaja.Hit(new Vector(300, 100));

        Keyboard.Listen(Key.Up, ButtonState.Pressed, LiikutaPelaajaa, "Lyö pelaajaa ylöspäin", pelaaja, new Vector(0, 500));
        Keyboard.Listen(Key.Down, ButtonState.Pressed, LiikutaPelaajaa, "Lyö pelaajaa alaspäin", pelaaja, new Vector(0, -500));
        Keyboard.Listen(Key.Right, ButtonState.Pressed, LiikutaPelaajaa, "Lyö pelaajaa oikealle", pelaaja, new Vector(500, 0));
        Keyboard.Listen(Key.Left, ButtonState.Pressed, LiikutaPelaajaa, "Lyö pelaajaa vasemmalle", pelaaja, new Vector(-500, 0));


        Keyboard.Listen(Key.Escape, ButtonState.Pressed, Exit, "Poistu");
        Keyboard.Listen(Key.F1, ButtonState.Pressed, ShowControlHelp, "Näytä näppäinohjeet");

        PhoneBackButton.Listen(ConfirmExit, "Lopeta peli");
        Keyboard.Listen(Key.Escape, ButtonState.Pressed, ConfirmExit, "Lopeta peli");
    }



    private void LiikutaPelaajaa(PhysicsObject pelaaja, Vector suunta)
    {
        pelaaja.Hit(suunta);
    }

    

    private void LuoKentta()
    {
        //TODO: Tässä olisi ajatus piirtää kentälle objekteja taulukossa.
        //objektit ovat osa kentän rakennetta, joihin pelaaja voi törmätä.
        //Törmäyksestä ei aiheudu pelaajalle mitään. Estävät vain liikkumista.
        //Myös mahdollinen taustakuva (tähdistö) tuotetaan tässä aliohjelmassa

        string[] kentta =
            {
            "                             ",
            "=   *         *             =",
            "=   ==        ==            =",
            "=         *                 =",
            "=        ==                 =",
            "=                      *    =",
            "=                   ====    =",
            "=    =                      =",
            "=                           =",
            "=              ==           =",
            "=                           =",
            "=     *                     =",
            "=     =                     =",
            "=              ==           =",
            "=                           =",
            "=                           =",
            "=       *                   =",
            "=      ===                  =",
            "=                   ==      =",
            "=                           =",
            "=   *             *         =",
            "============================="
            };

        TileMap ruudut = TileMap.FromStringArray(kentta);

        ruudut['='] = LuoPalikka;
        ruudut['*'] = LuoTahti;
        ruudut.Insert(ruudunLeveys, ruudunKorkeus);

        PhysicsObject LuoPalikka()
        {
            PhysicsObject palikka = PhysicsObject.CreateStaticObject(50.0, 50.0);
            palikka.Shape = Shape.Rectangle;
            palikka.Color = Color.DarkBrown;
            return palikka;
        }

        PhysicsObject LuoTahti()
        {
            PhysicsObject tahti = PhysicsObject.CreateStaticObject(25.0, 25.0);
            tahti.Color = Color.Yellow;
            return tahti;
        }


    }


public void Pahis ()
{
    //TODO: Tämä aliohjelma luo peliin pahat colesterol-ökrkit.
    //Jos pelaaja törmää tällaiseen, sydän heikkenee.


    for (int i = 0; i < 20; i++)
    {
        PhysicsObject pahis = new PhysicsObject(50.0, 50.0, Shape.Circle);
        RandomMoverBrain aivo = new RandomMoverBrain(70);
        Double arvo = RandomGen.NextDouble(15, 80);
        pahis.Color = Color.Red;
        pahis.Y = arvo;
        pahis.X = arvo;
        pahis.Brain = aivo;
        Add(pahis);
    }
        
}

    public void Hyvis()
        {
            //TODO: Tämä aliohjelma generoi peliin hyvikset (hyvät lipoproteiinit)
            //Näitä syömällä sydän vahvistuu. Syöminen tapahtuu törmäämällä

            for (int i = 0; i < 20; i++)
            {

                PhysicsObject hyvis = new PhysicsObject(50.0, 50.0, Shape.Triangle);
                RandomMoverBrain aivo = new RandomMoverBrain(200);
                Double arvo = RandomGen.NextDouble(10, 100);
                hyvis.Color = Color.Green;
                hyvis.Y = arvo;
                hyvis.X = arvo;
                hyvis.Brain = aivo;
                Add(hyvis);
            }

        }

    //public void PelaajaTormasi(PhysicsObject pelaaja, PhysicsObject paha)

    //{
    //    Explosion rajahdys = new Explosion(pahis.Width * 2);
    //    rajahdys.Position = pahis.Position;
    //    this.Add(rajahdys);
    //}


}
